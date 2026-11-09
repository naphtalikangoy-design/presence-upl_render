<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{JournalAudit, MesureRssi, PointAcces, SessionReseau, User};
use Illuminate\Http\Request;

/**
 * Réception d'événements réseau SIGNÉS (HMAC-SHA256) envoyés par le relais FreeRADIUS / le contrôleur.
 * En-têtes : X-UPL-Timestamp (secondes) et X-UPL-Signature = hex(HMAC_SHA256(secret, timestamp + "." + corps brut)).
 */
class NetworkEventController extends Controller
{
    public function __invoke(Request $r)
    {
        $secret = config('upl.webhook_secret');
        abort_unless($secret, 503, 'Intégration réseau non configurée.');
        $ts = (int) $r->header('X-UPL-Timestamp');
        abort_if(abs(time() - $ts) > config('upl.webhook_max_skew_seconds'), 401, 'Horodatage invalide.');
        $expected = hash_hmac('sha256', $ts . '.' . $r->getContent(), $secret);
        if (!hash_equals($expected, (string) $r->header('X-UPL-Signature'))) {
            JournalAudit::noter('reseau.webhook_signature_invalide', $r);
            abort(401, 'Signature invalide.');
        }
        $d = $r->validate([
            'event' => 'required|in:session_start,session_update,session_stop,rssi_report',
            'username' => 'required|string|max:80', 'session_id' => 'nullable|string|max:80', 'ip' => 'nullable|ip',
            'ssid' => 'nullable|string|max:64', 'ap_mac' => 'nullable|string|max:64', 'client_mac' => 'nullable|string|max:64',
            'rssi' => 'nullable|integer|between:-120,0',
            'readings' => 'nullable|array|max:20', 'readings.*.ap_mac' => 'required|string|max:64',
            'readings.*.rssi' => 'required|integer|between:-120,0', 'readings.*.band' => 'nullable|in:2.4,5,6',
        ]);
        $apId = PointAcces::normalize($d['ap_mac'] ?? null);
        $hash = !empty($d['client_mac']) ? hash_hmac('sha256', PointAcces::normalize($d['client_mac']), config('app.key')) : null;
        $q = SessionReseau::where('est_demo', false)->where('username', $d['username'])->whereNull('fin_at');
        if (!empty($d['session_id'])) $q->where('session_id_externe', $d['session_id']);
        $s = $q->latest('id')->first() ?? new SessionReseau(['username' => $d['username'], 'est_demo' => false, 'debut_at' => now(), 'source' => 'radius']);
        $s->fill(array_filter([
            'user_id' => User::where('matricule', $d['username'])->value('id'), 'session_id_externe' => $d['session_id'] ?? null,
            'ip_address' => $d['ip'] ?? null, 'ssid' => $d['ssid'] ?? null, 'terminal_mac_hash' => $hash,
            'ap_identifiant' => $apId, 'point_acces_id' => $apId ? PointAcces::where('identifiant', $apId)->value('id') : null,
        ], fn ($v) => $v !== null));
        $s->derniere_activite_at = now();
        if ($d['event'] === 'session_stop') $s->fin_at = now();
        $s->save();
        if ($s->point_acces_id) PointAcces::where('id', $s->point_acces_id)->update(['derniere_vue_at' => now()]);

        $readings = $d['readings'] ?? [];
        if (isset($d['rssi']) && $apId) $readings[] = ['ap_mac' => $apId, 'rssi' => $d['rssi'], 'band' => null];
        foreach ($readings as $m) {
            $ap = PointAcces::normalize($m['ap_mac']);
            MesureRssi::create(['session_reseau_id' => $s->id, 'point_acces_id' => PointAcces::where('identifiant', $ap)->value('id'), 'ap_identifiant' => $ap,
                'terminal_mac_hash' => $s->terminal_mac_hash ?? hash_hmac('sha256', 'session-' . $s->id, config('app.key')),
                'rssi_dbm' => $m['rssi'], 'bande' => $m['band'] ?? null, 'mesure_at' => now(), 'source' => 'radius', 'est_demo' => false]);
        }
        return response()->json(['ok' => true, 'session' => $s->id]);
    }
}
