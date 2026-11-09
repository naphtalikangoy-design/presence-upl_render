<?php
namespace App\Services\Network;

use App\Models\User;
use Illuminate\Http\Request;
use RuntimeException;

class NetworkVerifierManager
{
    public function driver(): NetworkVerifier
    {
        if (config('upl.network_mode') === 'demo') {
            if (app()->isProduction()) throw new RuntimeException('UPL_NETWORK_MODE=demo est interdit en production.');
            return new DemoNetworkVerifier();
        }
        return new RadiusNetworkVerifier();
    }

    public function verify(User $user, Request $request): NetworkCheckResult
    {
        if (!config('upl.wifi_required')) return new NetworkCheckResult(false, 'desactive', 'Obligation Wi-Fi désactivée : localisation intérieure impossible.');
        return $this->driver()->verify($user, $request);
    }
}
