<?php
namespace App\Services\Network;

use App\Models\{MesureRssi, PointAcces, SessionReseau, User};
use Illuminate\Http\Request;

/** MODE DÉMO UNIQUEMENT — aucune connexion réseau réelle n'est validée (est_demo = true). */
class DemoNetworkVerifier implements NetworkVerifier
{
    public function verify(User $user, Request $request): NetworkCheckResult
    {
        $scenario = config('upl.demo_scenario');
        $hash = hash_hmac('sha256', 'demo-' . $user->id, config('app.key'));
        MesureRssi::where('terminal_mac_hash', $hash)->where('est_demo', true)->delete();
        $ap = $scenario === 'ap_inconnu' ? null : PointAcces::where('actif', true)->orderBy('id')->first();
        $session = SessionReseau::updateOrCreate(['user_id' => $user->id, 'est_demo' => true], [
            'username' => $user->matricule, 'ip_address' => $request->ip(), 'ssid' => config('upl.allowed_ssid'),
            'ap_identifiant' => $scenario === 'ap_inconnu' ? 'ffffffffffff' : $ap?->identifiant, 'point_acces_id' => $ap?->id,
            'source' => 'demo', 'terminal_mac_hash' => $hash, 'debut_at' => now(), 'derniere_activite_at' => now(), 'fin_at' => null,
        ]);
        if ($scenario === 'reseau_non_autorise')
            return new NetworkCheckResult(false, 'demo', 'SIMULATION : réseau non autorisé.', null, true);
        return new NetworkCheckResult(true, 'demo', 'SIMULATION : aucun contrôle réseau réel (mode démo).', $session, true);
    }
}
