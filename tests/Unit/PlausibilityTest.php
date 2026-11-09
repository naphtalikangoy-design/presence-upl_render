<?php
namespace Tests\Unit;

use App\Services\GeoService;
use Tests\TestCase;

class PlausibilityTest extends TestCase
{
    public function test_detections(): void
    {
        $g = new GeoService(); $ts = now()->getTimestamp() * 1000;
        $this->assertSame([], $g->plausibilityIssues(-11.68, 27.5, 12, $ts));
        $this->assertContains('position_zero_zero', $g->plausibilityIssues(0, 0, 12, $ts));
        $this->assertContains('precision_irrealiste', $g->plausibilityIssues(-11.68, 27.5, 0.2, $ts));
        $this->assertContains('coordonnees_hors_limites', $g->plausibilityIssues(100, 27.5, 12, $ts));
        $this->assertContains('position_trop_ancienne_ou_horloge_incoherente', $g->plausibilityIssues(-11.68, 27.5, 12, $ts - 3600_000));
        $this->assertContains('deplacement_impossible', $g->plausibilityIssues(-11.68, 27.5, 12, $ts, ['lat' => -4.3, 'lon' => 15.3, 'at' => now()->subMinutes(5)]));
    }
}
