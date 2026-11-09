<?php
namespace App\Services;

use App\Models\ZoneGeographique;

class GeoService
{
    public const EARTH_RADIUS_M = 6371000.0;

    /** Distance orthodromique (Haversine) en mètres. */
    public static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $p1 = deg2rad($lat1); $p2 = deg2rad($lat2);
        $dp = $p2 - $p1; $dl = deg2rad($lon2 - $lon1);
        $a = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;
        return 2 * self::EARTH_RADIUS_M * asin(min(1.0, sqrt($a)));
    }

    /** @return array{zone: ?ZoneGeographique, distance: ?float, inside: bool} zone active la plus proche. */
    public function locate(float $lat, float $lon, float $accuracy = 0.0): array
    {
        $best = null; $bestDist = null;
        foreach (ZoneGeographique::with('batiment')->where('actif', true)
                     ->whereHas('batiment', fn ($q) => $q->where('actif', true))->get() as $zone) {
            $d = self::haversine($lat, $lon, $zone->latitude, $zone->longitude);
            if ($bestDist === null || $d < $bestDist) { $best = $zone; $bestDist = $d; }
        }
        if (!$best) return ['zone' => null, 'distance' => null, 'inside' => false];
        $effective = config('upl.gps.strict_accuracy_in_radius') ? $bestDist + $accuracy : $bestDist;
        return ['zone' => $best, 'distance' => $bestDist, 'inside' => $effective <= $best->rayon_m];
    }

    /**
     * Contrôles de cohérence. Limite : un navigateur ne peut PAS prouver l'authenticité d'une
     * position (extensions, émulateurs, appareils rootés). Ces contrôles réduisent la fraude
     * naïve ; la présence réelle repose sur la combinaison GPS + session Wi-Fi côté serveur.
     * @return string[] liste de raisons de suspicion (vide = rien de détecté)
     */
    public function plausibilityIssues(float $lat, float $lon, float $accuracy, ?int $positionTimestampMs,
                                       ?array $previous = null): array
    {
        $issues = [];
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) $issues[] = 'coordonnees_hors_limites';
        if ($lat == 0.0 && $lon == 0.0) $issues[] = 'position_zero_zero';
        if ($accuracy < config('upl.gps.min_plausible_accuracy_m')) $issues[] = 'precision_irrealiste';
        if ($positionTimestampMs !== null) {
            $age = abs(now()->getTimestamp() - intdiv($positionTimestampMs, 1000));
            if ($age > config('upl.gps.max_position_age_seconds')) $issues[] = 'position_trop_ancienne_ou_horloge_incoherente';
        }
        if ($previous) { // ['lat','lon','at' => Carbon]
            $dist = self::haversine($previous['lat'], $previous['lon'], $lat, $lon);
            $hours = max(1 / 3600, $previous['at']->diffInSeconds(now()) / 3600);
            if ($dist > 200 && ($dist / 1000) / $hours > config('upl.gps.max_speed_kmh')) $issues[] = 'deplacement_impossible';
        }
        return $issues;
    }
}
