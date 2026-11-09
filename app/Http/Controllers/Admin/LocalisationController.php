<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Batiment, EmpreinteWifi, JournalAudit, MesureRssi, PointAcces, SessionReseau, ZoneInterieure};
use App\Services\Localisation\RssiLocalizationService;
use App\Services\Wifi\{ConnectorManager, TxPowerService, UnsupportedOperationException};
use Illuminate\Http\Request;

class LocalisationController extends Controller
{
    public function __construct(private RssiLocalizationService $loc) {}

    public function mesures(Request $r)
    {
        $min = (int) $r->query('minutes', 60);
        $rows = MesureRssi::with('pointAcces')->where('mesure_at', '>=', now()->subMinutes(max(1, min($min, 1440))))
            ->when($r->filled('ap'), fn ($q) => $q->where('point_acces_id', $r->ap))->latest('mesure_at')->limit(200)->get();
        $moy = $rows->groupBy('ap_identifiant')->map(fn ($g) => round($g->avg('rssi_dbm'), 1));
        return view('admin.localisation.mesures', ['rows' => $rows, 'moy' => $moy, 'aps' => PointAcces::orderBy('nom')->get(), 'minutes' => $min]);
    }

    public function connexions(Request $r)
    {
        $rows = SessionReseau::with('pointAcces', 'user')->when($r->filled('q'), fn ($q) => $q->where('username', 'like', '%' . $r->q . '%'))
            ->when($r->boolean('actives'), fn ($q) => $q->whereNull('fin_at'))->latest('derniere_activite_at')->paginate(30)->withQueryString();
        return view('admin.localisation.connexions', ['rows' => $rows]);
    }

    public function etat(ConnectorManager $cm)
    {
        $ttl = config('upl.network_session_ttl_minutes');
        $aps = PointAcces::with('batiment')->orderBy('batiment_id')->get()->map(function ($ap) use ($ttl) {
            $ap->en_ligne = $ap->derniere_vue_at && $ap->derniere_vue_at->gt(now()->subMinutes($ttl));
            $ap->clients = SessionReseau::where('point_acces_id', $ap->id)->whereNull('fin_at')->where('derniere_activite_at', '>=', now()->subMinutes($ttl))->count();
            $ap->mesures15 = MesureRssi::where('point_acces_id', $ap->id)->where('mesure_at', '>=', now()->subMinutes(15))->count();
            return $ap;
        });
        $c = $cm->driver();
        return view('admin.localisation.etat', ['aps' => $aps, 'connecteur' => $c->key(), 'caps' => $c->capabilities(), 'ttl' => $ttl]);
    }

    // ---------- Calibration ----------
    public function calibration()
    {
        return view('admin.localisation.calibration', ['fps' => EmpreinteWifi::with('batiment', 'zone')->latest('id')->limit(300)->get(), 'batiments' => Batiment::orderBy('nom')->get(),
            'zones' => ZoneInterieure::orderBy('nom')->get(), 'min' => config('upl.rssi.min_fingerprints_per_class'),
            'compte' => EmpreinteWifi::where('actif', true)->get()->groupBy('batiment_id')->map(fn ($g) => $g->groupBy(fn ($f) => $f->classeEffective())->map->count())]);
    }

    private function median(array $v): float { sort($v); $n = count($v); return $n % 2 ? $v[intdiv($n, 2)] : ($v[$n / 2 - 1] + $v[$n / 2]) / 2; }

    public function calibrer(Request $r)
    {
        $d = $r->validate(['batiment_id' => 'required|exists:batiments,id', 'classe' => 'required|in:interieur,exterieur', 'zone_interieure_id' => 'nullable|exists:zones_interieures,id',
            'appareil' => 'nullable|max:80', 'username' => 'nullable|string|max:80', 'signature' => 'nullable|string|max:2000']);
        if (!empty($d['zone_interieure_id']) && !ZoneInterieure::where('id', $d['zone_interieure_id'])->where('batiment_id', $d['batiment_id'])->exists())
            return back()->withInput()->with('err', 'Cette zone n\'appartient pas au bâtiment choisi.');
        $nb = 1;
        if (!empty($d['signature'])) {                       // saisie manuelle / import d'un relevé de site survey
            $raw = json_decode($d['signature'], true); $sig = [];
            if (!is_array($raw) || !$raw) return back()->withInput()->with('err', 'Signature JSON invalide. Format : {"aabbccddeeff": -52, ...}');
            foreach ($raw as $mac => $rssi) {
                $k = PointAcces::normalize((string) $mac);
                if (!$k || !is_numeric($rssi) || $rssi < -120 || $rssi > 0) return back()->withInput()->with('err', "Entrée invalide : $mac => $rssi");
                $sig[$k] = (int) $rssi;
            }
        } elseif (!empty($d['username'])) {                  // capture réelle depuis la session Wi-Fi d'un téléphone de calibration
            $s = SessionReseau::where('username', $d['username'])->whereNull('fin_at')->where('derniere_activite_at', '>=', now()->subMinutes(config('upl.network_session_ttl_minutes')))->latest('derniere_activite_at')->first();
            if (!$s) return back()->withInput()->with('err', 'Aucune session Wi-Fi active pour cet identifiant : connectez le téléphone de calibration au SSID officiel.');
            $samples = $this->loc->collect($s);
            if (!$samples) return back()->withInput()->with('err', 'Aucune mesure RSSI disponible (le connecteur actif ne fournit peut-être pas le RSSI).');
            $nb = min(array_map('count', $samples));
            if ($nb < 3) return back()->withInput()->with('err', "Échantillons insuffisants ($nb) : laissez le téléphone connecté quelques instants puis réessayez (3 minimum).");
            $sig = array_map(fn ($v) => (int) round($this->median($v)), $samples);
        } else return back()->withInput()->with('err', 'Fournissez un identifiant de session (capture) ou une signature JSON.');

        $e = EmpreinteWifi::create(['batiment_id' => $d['batiment_id'], 'zone_interieure_id' => $d['classe'] === 'interieur' ? ($d['zone_interieure_id'] ?? null) : null, 'classe' => $d['classe'],
            'signature' => $sig, 'nb_echantillons' => $nb, 'appareil' => $d['appareil'] ?? null, 'releve_par' => $r->user()->id, 'releve_at' => now(), 'actif' => true, 'est_demo' => config('upl.network_mode') === 'demo']);
        JournalAudit::noter('admin.calibration.empreinte', $r, $e, ['classe' => $d['classe'], 'aps' => count($sig)]);
        return back()->with('ok', 'Empreinte enregistrée (' . count($sig) . ' AP, ' . $nb . ' échantillon(s)).');
    }

    public function basculer(Request $r, EmpreinteWifi $empreinte)
    {
        $empreinte->update(['actif' => !$empreinte->actif]); JournalAudit::noter('admin.calibration.bascule', $r, $empreinte);
        return back()->with('ok', 'Empreinte ' . ($empreinte->actif ? 'activée' : 'désactivée') . '.');
    }

    public function supprimer(Request $r, EmpreinteWifi $empreinte)
    {
        JournalAudit::noter('admin.calibration.suppression', $r, $empreinte); $empreinte->delete();
        return back()->with('ok', 'Empreinte supprimée.');
    }

    // ---------- Tests de localisation ----------
    public function test() { return view('admin.localisation.test', ['res' => null]); }

    public function tester(Request $r)
    {
        $d = $r->validate(['username' => 'required|string|max:80']);
        $s = SessionReseau::with('pointAcces.batiment')->where('username', $d['username'])->whereNull('fin_at')->where('derniere_activite_at', '>=', now()->subMinutes(config('upl.network_session_ttl_minutes')))->latest('derniere_activite_at')->first();
        if (!$s) return view('admin.localisation.test', ['res' => ['erreur' => 'Aucune session Wi-Fi active pour « ' . e($d['username']) . ' ». En mode démo, la session est créée quand le stagiaire ouvre la page de pointage.']]);
        $bat = $s->pointAcces?->batiment;
        if (!$bat) return view('admin.localisation.test', ['res' => ['erreur' => 'Session trouvée mais l\'Access Point (' . ($s->ap_identifiant ?? 'inconnu') . ') n\'est pas déclaré : ACCESS POINT NON AUTORISÉ.', 'session' => $s]]);
        $fps = $this->loc->fingerprintsFor($bat->id); $samples = $this->loc->collect($s); $res = $this->loc->classify($samples, $fps);
        JournalAudit::noter('admin.localisation.test', $r, $s, ['username' => $d['username']]);
        return view('admin.localisation.test', ['res' => ['session' => $s, 'batiment' => $bat, 'resultat' => $res, 'nbFps' => $fps->count(), 'samples' => $samples,
            'seuil' => $res->methode === 'fingerprint_knn' ? config('upl.rssi.min_confidence') : config('upl.rssi.min_confidence_single')]]);
    }

    // ---------- Puissance d'émission ----------
    public function puissance(ConnectorManager $cm)
    {
        $c = $cm->driver();
        return view('admin.localisation.puissance', ['aps' => PointAcces::with('batiment')->orderBy('nom')->get(), 'connecteur' => $c->key(), 'peut' => (bool) ($c->capabilities()['tx_power'] ?? false)]);
    }

    public function appliquerPuissance(Request $r, PointAcces $pointAcces, TxPowerService $svc)
    {
        $d = $r->validate(['puissance_dbm' => 'required|integer|between:-10,50', 'bande' => 'required|in:2.4,5', 'canal' => 'nullable|integer|between:1,177']);
        try {
            $res = $svc->apply($pointAcces, $d, $r);
        } catch (UnsupportedOperationException | \InvalidArgumentException $e) {
            JournalAudit::noter('admin.puissance.refusee', $r, $pointAcces, ['raison' => $e->getMessage()]);
            return back()->with('err', 'Opération refusée : ' . $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('err', 'Échec de communication avec le contrôleur : ' . $e->getMessage());
        }
        return back()->with($res['confirmed'] ? 'ok' : 'err', $res['confirmed'] ? 'Le matériel a confirmé la modification. Pensez à recalibrer les empreintes de ce bâtiment.' : 'Le matériel n\'a PAS confirmé la modification : rien n\'a été enregistré.');
    }
}
