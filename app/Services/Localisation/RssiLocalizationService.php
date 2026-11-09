<?php
namespace App\Services\Localisation;

use App\Models\{EmpreinteWifi, MesureRssi, SessionReseau};
use App\Services\Wifi\ConnectorManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Classification intérieur/extérieur par Wi-Fi fingerprinting (kNN pondéré) — PROBABILISTE, jamais une certitude.
 * - ≥ 2 AP mesurés : kNN sur l'espace des signaux, confiance = part pondérée de la classe gagnante.
 * - 1 AP mesuré : méthode limitée (vraisemblance gaussienne par classe), confiance plafonnée.
 * - Pas de seuil RSSI universel : la décision compare la mesure à des références relevées sur place.
 */
class RssiLocalizationService
{
    public function __construct(private ConnectorManager $connectors) {}

    /** Récupère les mesures récentes (push en base, sinon pull via le connecteur si capable). @return array<string,int[]> */
    public function collect(SessionReseau $s): array
    {
        $since = now()->subSeconds(config('upl.rssi.window_seconds'));
        $q = fn () => MesureRssi::where('mesure_at', '>=', $since)
            ->where(fn ($w) => $s->terminal_mac_hash ? $w->where('terminal_mac_hash', $s->terminal_mac_hash) : $w->where('session_reseau_id', $s->id))->get();
        $rows = $q();
        if ($rows->isEmpty()) {
            $conn = $this->connectors->driver();
            if ($conn->capabilities()['client_rssi'] ?? false) {
                try {
                    foreach ($conn->fetchClientRssi($s) as $m) {
                        $ap = \App\Models\PointAcces::normalize($m['ap_mac']);
                        if (!$ap) continue;
                        MesureRssi::create(['session_reseau_id' => $s->id, 'point_acces_id' => \App\Models\PointAcces::where('identifiant', $ap)->value('id'),
                            'ap_identifiant' => $ap, 'terminal_mac_hash' => $s->terminal_mac_hash ?? hash_hmac('sha256', 'session-' . $s->id, config('app.key')),
                            'rssi_dbm' => $m['rssi'], 'bande' => $m['band'] ?? null, 'mesure_at' => now(), 'source' => $conn->key(), 'est_demo' => $conn->key() === 'demo']);
                    }
                } catch (\Throwable $e) { Log::warning('Collecte RSSI impossible : ' . $e->getMessage()); }
                $rows = $q();
            }
        }
        $by = [];
        foreach ($rows as $r) $by[$r->ap_identifiant][] = (int) $r->rssi_dbm;
        return $by;
    }

    /** @param array<string,int[]> $samples @param Collection<int,EmpreinteWifi> $fps */
    public function classify(array $samples, Collection $fps): LocalizationResult
    {
        $cfg = config('upl.rssi');
        if (!$samples) return new LocalizationResult('inconnue', 0.0, 'aucune', 'nulle', false, false, false, ['raison' => 'aucune_mesure']);

        $medians = []; $instable = false;
        foreach ($samples as $ap => $vals) {
            sort($vals); $n = count($vals); $medians[$ap] = $n % 2 ? $vals[intdiv($n, 2)] : ($vals[$n / 2 - 1] + $vals[$n / 2]) / 2;
            if ($n >= 3) { $mean = array_sum($vals) / $n; $sd = sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $vals)) / $n); if ($sd > $cfg['unstable_std_db']) $instable = true; }
        }
        $signalOk = max($medians) >= $cfg['min_rssi_dbm'];
        $base = ['medians' => $medians];
        if (!$signalOk) return new LocalizationResult('inconnue', 0.0, 'signal_faible', 'nulle', true, false, $instable, $base);

        $byClass = $fps->groupBy(fn (EmpreinteWifi $f) => $f->classeEffective());
        $min = $cfg['min_fingerprints_per_class'];
        if (($byClass['interieur'] ?? collect())->count() < $min || ($byClass['exterieur'] ?? collect())->count() < $min)
            return new LocalizationResult('inconnue', 0.0, 'calibration_insuffisante', 'nulle', true, true, $instable, $base + ['raison' => 'calibration_insuffisante']);

        return count($medians) >= 2 ? $this->knn($medians, $fps, $instable, $base) : $this->single($medians, $byClass, $instable, $base);
    }

    private function knn(array $m, Collection $fps, bool $instable, array $base): LocalizationResult
    {
        $cfg = config('upl.rssi'); $floor = $cfg['floor_dbm'];
        $dists = $fps->map(function (EmpreinteWifi $f) use ($m, $floor) {
            $sig = $f->signature; $keys = array_unique(array_merge(array_keys($m), array_keys($sig)));
            $sum = 0; foreach ($keys as $k) $sum += (($m[$k] ?? $floor) - ($sig[$k] ?? $floor)) ** 2;
            return ['d' => sqrt($sum / count($keys)), 'c' => $f->classeEffective()];
        })->sortBy('d')->values();
        $nearest = $dists->take(min($cfg['k'], $dists->count()));
        $base['distance_min_db'] = round($nearest->first()['d'], 2);
        if ($nearest->first()['d'] > $cfg['max_reference_distance_db'])
            return new LocalizationResult('inconnue', 0.0, 'hors_reference', 'nulle', true, true, $instable, $base);
        $w = ['interieur' => 0.0, 'exterieur' => 0.0];
        foreach ($nearest as $n) $w[$n['c']] += 1 / ($n['d'] + 0.5);
        $classe = $w['interieur'] >= $w['exterieur'] ? 'interieur' : 'exterieur';
        $conf = $w[$classe] / array_sum($w);
        if ($instable) $conf *= 0.85;
        return new LocalizationResult($classe, round($conf, 3), 'fingerprint_knn', 'eleve', true, true, $instable, $base);
    }

    private function single(array $m, Collection $byClass, bool $instable, array $base): LocalizationResult
    {
        $cfg = config('upl.rssi'); $ap = array_key_first($m); $x = $m[$ap];
        $ll = [];
        foreach (['interieur', 'exterieur'] as $c) {
            $vals = $byClass[$c]->map(fn ($f) => $f->signature[$ap] ?? null)->filter(fn ($v) => $v !== null)->values()->all();
            if (count($vals) < $cfg['min_fingerprints_per_class'])
                return new LocalizationResult('inconnue', 0.0, 'calibration_insuffisante', 'nulle', true, true, $instable, $base + ['raison' => 'pas_assez_de_references_pour_cet_ap']);
            $mu = array_sum($vals) / count($vals);
            $sd = max(3.0, sqrt(array_sum(array_map(fn ($v) => ($v - $mu) ** 2, $vals)) / count($vals)));
            $ll[$c] = -0.5 * (($x - $mu) / $sd) ** 2 - log($sd);
        }
        $pInt = 1 / (1 + exp(max(-50, min(50, $ll['exterieur'] - $ll['interieur']))));
        $classe = $pInt >= 0.5 ? 'interieur' : 'exterieur';
        $conf = min(max($pInt, 1 - $pInt), $cfg['single_ap_max_confidence']);
        if ($instable) $conf *= 0.85;
        return new LocalizationResult($classe, round($conf, 3), 'ap_seul', 'moyen', true, true, $instable, $base);
    }

    public function fingerprintsFor(int $batimentId): Collection
    {
        return EmpreinteWifi::with('zone')->where('batiment_id', $batimentId)->where('actif', true)->get();
    }
}
