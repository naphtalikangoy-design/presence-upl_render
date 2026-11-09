<?php
namespace App\Services\Wifi;

class ConnectorManager
{
    public function driver(): WifiConnector
    {
        if (config('upl.network_mode') === 'demo') {
            if (app()->isProduction()) throw new \RuntimeException('UPL_NETWORK_MODE=demo est interdit en production.');
            return new DemoConnector();
        }
        return match (config('upl.connector')) {
            'generic_rest' => new GenericRestConnector(config('upl_controller')),
            default => new FreeRadiusConnector(),
        };
    }
}
