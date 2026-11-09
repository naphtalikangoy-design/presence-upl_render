<?php
namespace App\Services;

use App\Enums\StatutPointage as S;
use App\Models\{DefiPointage, Horaire, JourOuvrable, JournalAudit, JournalPointage, Presence, User, ValidationLocalisation, ZoneGeographique};
use App\Services\Localisation\{LocalizationResult, RssiLocalizationService};
use App\Services\Network\NetworkVerifierManager;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Décision de pointage 100 % côté serveur. Le navigateur ne fournit que sa position GPS (non fiable) et un nonce ;
 * l'identité réseau, l'AP, le RSSI et le bâtiment viennent de l'infrastructure (sessions_reseau / mesures_rssi).
 */
class PointageService
{
    public function __construct(private GeoService $geo, private NetworkVerifierManager $network, private RssiLocalizationService $rssi) {}

    /** Évalue toutes les conditions SANS enregistrer. @return array{ok:bool,statut:S,code:string,message:string,checks:array,ctx:array} */
    public function evaluate(User $user, Request $request, ?array $pos, string $type = 'arrivee'): array
    {
        $checks = ['wifi_connecte' => false, 'reseau_officiel' => false, 'access_point' => false, 'signal' => false,
                   'batiment' => false, 'gps' => false, 'autorisation' => false];
        $ctx = [];
        $out = function (S $s) use (&$checks, &$ctx) {
            $checks['autorisation'] = $s === S::PRESENCE_VALIDEE;
            return ['ok' => $checks['autorisation'], 'statut' => $s, 'code' => $s->value, 'message' => $s->message(), 'checks' => $checks, 'ctx' => $ctx];
        };
        $ac = config('upl.anti_fraude');

        // Compte actif + profil
        $st = $user->stagiaire;
        if (!$user->isActif() || !$st) return $out(S::COMPTE_INACTIF);
        $ctx['stagiaire'] = $st;

        // Limitation des tentatives refusées
        $refus = JournalPointage::where('user_id', $user->id)->where('created_at', '>=', now()->subMinutes($ac['window_minutes']))
            ->whereNotIn('statut', [S::PRESENCE_VALIDEE->value, S::POINTAGE_DEJA_EFFECTUE->value])->count();
        if ($refus >= $ac['max_refused_attempts']) return $out(S::TROP_DE_TENTATIVES);

        // Période autorisée
        $now = now(); $h = Horaire::where('actif', true)->latest('id')->first();
        $jourOk = JourOuvrable::where('jour_semaine', $now->dayOfWeekIso)->where('actif', true)->exists();
        $stageOk = (!$st->date_debut_stage || $now->toDateString() >= $st->date_debut_stage->toDateString())
                && (!$st->date_fin_stage || $now->toDateString() <= $st->date_fin_stage->toDateString());
        if (!$h || !$jourOk || !$stageOk || $now->format('H:i:s') < $h->fenetre_debut || $now->format('H:i:s') > $h->fenetre_fin) return $out(S::HORS_PERIODE);
        $ctx += ['now' => $now, 'horaire' => $h];

        // Doublon
        if (Presence::where('stagiaire_id', $st->id)->whereDate('date', $now->toDateString())->where('type', $type)->exists()) return $out(S::POINTAGE_DEJA_EFFECTUE);

        if ($type === 'depart' && !Presence::where('stagiaire_id', $st->id)->whereDate('date', $now->toDateString())->where('type', 'arrivee')->exists()) return $out(S::ARRIVEE_REQUISE);

        $wifiRequired = (bool) config('upl.wifi_required');
        $loc = null; $assurance = 'faible'; $batimentId = null; $session = null; $ap = null; $demo = false;

        if ($wifiRequired) {
            // Session Wi-Fi officielle (vérifiée par l'infrastructure)
            $net = $this->network->verify($user, $request);
            $demo = $net->isDemo; $ctx['net'] = $net; $session = $net->session;
            if (!$net->verified || !$session) return $out(S::WIFI_NON_AUTORISE);
            $checks['wifi_connecte'] = $checks['reseau_officiel'] = true;

            // Access Point autorisé → bâtiment
            $ap = $session->point_acces_id ? \App\Models\PointAcces::with('batiment')->find($session->point_acces_id) : null;
            if (!$ap || !$ap->actif || !$ap->batiment || !$ap->batiment->actif || !$ap->batiment->pointage_actif) return $out(S::ACCESS_POINT_NON_AUTORISE);
            $checks['access_point'] = $checks['batiment'] = true; $batimentId = $ap->batiment_id; $ctx += ['ap' => $ap, 'batiment' => $ap->batiment];

            // Localisation intérieure par empreintes RSSI
            $loc = $this->rssi->classify($this->rssi->collect($session), $this->rssi->fingerprintsFor($batimentId));
            $ctx['loc'] = $loc;
            if ($loc->donnees && !$loc->signalSuffisant) return $out(S::SIGNAL_INSUFFISANT);
            $checks['signal'] = $loc->donnees && $loc->signalSuffisant;
            if (!$loc->donnees) {
                if (config('upl.localisation_policy') !== 'ap_gps') return $out(S::LOCALISATION_NON_CONFIRMEE);
                $assurance = 'faible';
            } else {
                $need = $loc->methode === 'fingerprint_knn' ? config('upl.rssi.min_confidence') : config('upl.rssi.min_confidence_single');
                if ($loc->classe === 'exterieur' && $loc->confiance >= $need) return $out(S::HORS_DU_BATIMENT);
                if ($loc->classe !== 'interieur' || $loc->confiance < $need) return $out(S::LOCALISATION_NON_CONFIRMEE);
                $assurance = $loc->assurance;
            }
        } else { $checks['wifi_connecte'] = $checks['reseau_officiel'] = $checks['access_point'] = $checks['signal'] = false; }

        // GPS : contrôle complémentaire
        $gps = null; $gpsOk = false;
        $valid = $pos && isset($pos['latitude'], $pos['longitude'], $pos['accuracy']);
        if ($valid) {
            $lat = (float) $pos['latitude']; $lon = (float) $pos['longitude']; $acc = (float) $pos['accuracy'];
            $prev = Presence::where('stagiaire_id', $st->id)->latest('pointe_a')->first();
            $issues = $this->geo->plausibilityIssues($lat, $lon, $acc, $pos['timestamp'] ?? null,
                $prev ? ['lat' => $prev->latitude, 'lon' => $prev->longitude, 'at' => $prev->pointe_a] : null);
            if ($issues) { JournalAudit::noter('pointage.position_suspecte', $request, $st, ['raisons' => $issues]); return $out(S::POSITION_SUSPECTE); }
            if ($acc <= config('upl.gps.max_accuracy_m')) {
                $zones = ZoneGeographique::where('actif', true)->when($batimentId, fn ($q) => $q->where('batiment_id', $batimentId))
                    ->whereHas('batiment', fn ($q) => $q->where('actif', true)->where('pointage_actif', true))->get();
                $best = null; $bd = null;
                foreach ($zones as $z) { $d = GeoService::haversine($lat, $lon, $z->latitude, $z->longitude); if ($bd === null || $d < $bd) { $bd = $d; $best = $z; } }
                $eff = $bd !== null && config('upl.gps.strict_accuracy_in_radius') ? $bd + $acc : $bd;
                if (!$best || $eff > $best->rayon_m) return $out(S::HORS_DU_BATIMENT);
                $gpsOk = true; $gps = compact('lat', 'lon', 'acc') + ['distance' => $bd, 'batiment_id' => $best->batiment_id];
                $batimentId ??= $best->batiment_id;
            } else { $gps = compact('lat', 'lon', 'acc') + ['distance' => null, 'batiment_id' => null]; }
        }
        if (!$gpsOk) {
            // GPS absent/imprécis : toléré uniquement si l'empreinte multi-AP confirme l'intérieur (niveau d'assurance élevé)
            $tolere = $wifiRequired && $assurance === 'eleve' && config('upl.gps.optional_if_fingerprint');
            if (!$tolere) return $out(S::GPS_INDISPONIBLE);
        }
        $checks['gps'] = $gpsOk; $ctx += ['gps' => $gps, 'gps_ok' => $gpsOk, 'assurance' => $wifiRequired ? $assurance : 'faible', 'demo' => $demo, 'batiment_id' => $batimentId, 'session' => $session];
        return $out(S::PRESENCE_VALIDEE);
    }

    /** Émet un nonce à usage unique lié à l'utilisateur (anti-rejeu). */
    public function issueChallenge(User $user): string
    {
        $nonce = Str::random(48);
        DefiPointage::create(['user_id' => $user->id, 'nonce' => $nonce, 'expire_at' => now()->addSeconds(config('upl.anti_fraude.challenge_ttl_seconds'))]);
        return $nonce;
    }

    /** Évalue de nouveau, consomme le nonce, enregistre et journalise. */
    public function record(User $user, Request $request, ?array $pos, ?string $nonce, string $type = 'arrivee'): array
    {
        $r = $this->evaluate($user, $request, $pos, $type);
        $ctx = $r['ctx'];
        if ($r['ok']) {
            $defi = $nonce ? DefiPointage::where('user_id', $user->id)->where('nonce', $nonce)->whereNull('utilise_at')->where('expire_at', '>', now())->first() : null;
            if (!$defi || DefiPointage::where('id', $defi->id)->whereNull('utilise_at')->update(['utilise_at' => now()]) !== 1) {
                $r = ['ok' => false, 'statut' => S::DEFI_INVALIDE, 'code' => S::DEFI_INVALIDE->value, 'message' => S::DEFI_INVALIDE->message(), 'checks' => array_merge($r['checks'], ['autorisation' => false]), 'ctx' => $ctx];
            }
        }
        $presence = null;
        if ($r['ok']) {
            $st = $ctx['stagiaire']; $now = $ctx['now']; $h = $ctx['horaire']; $gps = $ctx['gps'];
            $retard = 0; $statut = 'present';
            if ($type === 'arrivee') {
                $lim = Carbon::parse($now->toDateString() . ' ' . $h->heure_limite);
                if ($now->gt($lim)) { $statut = 'retard'; $retard = (int) $lim->diffInMinutes($now); }
            }
            $net = $ctx['net'] ?? null;
            try {
                $presence = Presence::create([
                    'stagiaire_id' => $st->id, 'batiment_id' => $ctx['batiment_id'], 'point_acces_id' => $ctx['ap']->id ?? null,
                    'session_reseau_id' => $ctx['session']->id ?? null, 'date' => $now->toDateString(), 'type' => $type, 'pointe_a' => $now,
                    'latitude' => $gps['lat'] ?? 0, 'longitude' => $gps['lon'] ?? 0, 'precision_gps' => $gps['acc'] ?? 0, 'distance_m' => round($gps['distance'] ?? 0, 2),
                    'statut' => $statut, 'retard_minutes' => $retard,
                    'wifi_verifie' => $net && $net->verified && !$net->isDemo, 'wifi_source' => $net->source ?? 'desactive',
                ]);
            } catch (QueryException $e) {
                $r = ['ok' => false, 'statut' => S::POINTAGE_DEJA_EFFECTUE, 'code' => S::POINTAGE_DEJA_EFFECTUE->value, 'message' => S::POINTAGE_DEJA_EFFECTUE->message(), 'checks' => $r['checks'], 'ctx' => $ctx];
            }
        }
        if ($presence && $r['ok']) {
            $user->notifications()->create(['titre' => $type === 'depart' ? 'Départ enregistré' : 'Présence enregistrée',
                'message' => ($type === 'depart' ? 'Votre départ' : 'Votre arrivée') . ' a été enregistré' . ($type === 'depart' ? '' : 'e') . ' à ' . $presence->pointe_a->format('H:i')
                    . ($presence->statut === 'retard' ? " (retard de {$presence->retard_minutes} min)." : '.')]);
        }
        /** @var LocalizationResult|null $loc */
        $loc = $ctx['loc'] ?? null; $s = $r['statut'];
        if ($ctx['stagiaire'] ?? null) {
            ValidationLocalisation::create(['user_id' => $user->id, 'stagiaire_id' => $ctx['stagiaire']->id, 'session_reseau_id' => $ctx['session']->id ?? ($ctx['net']->session->id ?? null),
                'batiment_id' => $ctx['batiment_id'] ?? ($ctx['batiment']->id ?? null), 'statut' => $s->value, 'methode' => $loc?->methode, 'classe' => $loc?->classe,
                'confiance' => $loc?->confiance, 'assurance' => $ctx['assurance'] ?? null, 'gps_verifie' => $ctx['gps_ok'] ?? false,
                'details' => $loc ? ['medians' => $loc->details['medians'] ?? null, 'instable' => $loc->instable] : null, 'est_demo' => $ctx['demo'] ?? false]);
        }
        JournalPointage::create(['user_id' => $user->id, 'stagiaire_id' => $ctx['stagiaire']->id ?? null, 'statut' => $s->value, 'ip_address' => $request->ip(),
            'details' => ['presence_id' => $presence?->id, 'demo' => $ctx['demo'] ?? false]]);
        $r['presence'] = $presence; $r['demo'] = $ctx['demo'] ?? false;
        return $r;
    }
}
