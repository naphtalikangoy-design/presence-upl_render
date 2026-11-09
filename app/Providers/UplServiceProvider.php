<?php
namespace App\Providers;

use App\Models\ConfigurationReseau;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

/** Applique les paramètres modifiables depuis l'administration (table configurations_reseau, hors secrets).
 *  Mode réseau, connecteur et secrets restent EXCLUSIVEMENT dans .env. */
class UplServiceProvider extends ServiceProvider
{
    public const MAP = [
        'wifi_required' => ['upl.wifi_required', 'bool'], 'allowed_ssid' => ['upl.allowed_ssid', 'str'],
        'network_session_ttl_minutes' => ['upl.network_session_ttl_minutes', 'int'], 'require_same_ip' => ['upl.require_same_ip', 'bool'],
        'localisation_policy' => ['upl.localisation_policy', 'str'],
        'gps_max_accuracy_m' => ['upl.gps.max_accuracy_m', 'int'], 'gps_optional_if_fingerprint' => ['upl.gps.optional_if_fingerprint', 'bool'],
        'rssi_min_rssi_dbm' => ['upl.rssi.min_rssi_dbm', 'int'], 'rssi_min_confidence' => ['upl.rssi.min_confidence', 'float'],
        'rssi_min_confidence_single' => ['upl.rssi.min_confidence_single', 'float'],
        'rssi_min_fingerprints_per_class' => ['upl.rssi.min_fingerprints_per_class', 'int'],
        'max_refused_attempts' => ['upl.anti_fraude.max_refused_attempts', 'int'],
    ];

    public function boot(): void
    {
        if ($this->app->isProduction() || str_starts_with((string) config('app.url'), 'https://')) URL::forceScheme('https');
        try {
            foreach (ConfigurationReseau::where('est_secret', false)->pluck('valeur', 'cle') as $k => $v) {
                if (!isset(self::MAP[$k]) || $v === null) continue;
                [$path, $type] = self::MAP[$k];
                config([$path => match ($type) { 'bool' => filter_var($v, FILTER_VALIDATE_BOOLEAN), 'int' => (int) $v, 'float' => (float) $v, default => $v }]);
            }
        } catch (\Throwable $e) { /* tables absentes avant migration */ }
    }
}
