<?php
namespace App\Services\Network;

use App\Models\{SessionReseau, User};
use Illuminate\Http\Request;

/**
 * Vérification RÉELLE : se base sur la table sessions_reseau, alimentée UNIQUEMENT par
 * l'API signée /api/network/session-events (FreeRADIUS accounting / contrôleur Wi-Fi).
 * Rien n'est déduit du SSID ou de l'IP déclarés par le navigateur.
 */
class RadiusNetworkVerifier implements NetworkVerifier
{
    public function verify(User $user, Request $request): NetworkCheckResult
    {
        $ttl = config('upl.network_session_ttl_minutes');
        $q = SessionReseau::where('est_demo', false)->whereNull('fin_at')
            ->where('derniere_activite_at', '>=', now()->subMinutes($ttl))
            ->where(fn ($w) => $w->where('user_id', $user->id)->orWhere('username', $user->matricule));
        if (config('upl.require_same_ip')) {
            // Limite connue : derrière un NAT/proxy l'IP vue par l'app peut différer de celle du NAS.
            $q->where('ip_address', $request->ip());
        }
        $s = $q->latest('derniere_activite_at')->first();
        if (!$s) {
            return new NetworkCheckResult(false, 'radius', 'Aucune session Wi-Fi UPL active confirmée par le serveur réseau.');
        }
        if ($s->ssid && strcasecmp($s->ssid, config('upl.allowed_ssid')) !== 0) {
            return new NetworkCheckResult(false, 'radius', 'Le réseau Wi-Fi utilisé n\'est pas le réseau officiel.', $s);
        }
        return new NetworkCheckResult(true, $s->source, 'Session Wi-Fi confirmée par l\'infrastructure réseau.', $s);
    }
}
