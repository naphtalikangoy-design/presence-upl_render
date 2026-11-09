<?php
namespace Tests\Unit;
use App\Models\EmpreinteWifi;
use App\Services\Localisation\RssiLocalizationService;
use App\Services\Wifi\ConnectorManager;
use Tests\TestCase;

class RssiLocalizationTest extends TestCase
{
    private function fps(): \Illuminate\Support\Collection
    {
        $c = collect();
        foreach ([-2, 1, 3, -1, 2, 0] as $n) {
            $c->push(new EmpreinteWifi(['classe' => 'interieur', 'signature' => ['a' => -45 + $n, 'b' => -57 - $n, 'c' => -68 + $n]]));
            $c->push(new EmpreinteWifi(['classe' => 'exterieur', 'signature' => ['a' => -76 + $n, 'b' => -83 - $n, 'c' => -89 + $n]]));
        }
        return $c;
    }
    private function svc(): RssiLocalizationService { return new RssiLocalizationService(new ConnectorManager()); }

    public function test_interieur_multi_ap(): void
    {
        $r = $this->svc()->classify(['a' => [-46], 'b' => [-58], 'c' => [-67]], $this->fps());
        $this->assertSame('interieur', $r->classe); $this->assertGreaterThan(0.7, $r->confiance); $this->assertSame('eleve', $r->assurance);
    }
    public function test_exterieur_multi_ap(): void
    {
        $r = $this->svc()->classify(['a' => [-75], 'b' => [-84], 'c' => [-88]], $this->fps());
        $this->assertSame('exterieur', $r->classe);
    }
    public function test_mesure_qui_ne_ressemble_a_rien_n_est_pas_confirmee(): void
    {
        $r = $this->svc()->classify(['a' => [-62], 'b' => [-40], 'c' => [-79]], $this->fps());
        $this->assertSame('inconnue', $r->classe); $this->assertSame('hors_reference', $r->methode);
    }
    public function test_signal_insuffisant(): void
    {
        $r = $this->svc()->classify(['a' => [-90], 'b' => [-93]], $this->fps());
        $this->assertFalse($r->signalSuffisant);
    }
    public function test_aucune_donnee(): void
    {
        $r = $this->svc()->classify([], $this->fps()); $this->assertFalse($r->donnees); $this->assertSame('aucune', $r->methode);
    }
    public function test_calibration_insuffisante(): void
    {
        $r = $this->svc()->classify(['a' => [-46], 'b' => [-58]], $this->fps()->take(4)); $this->assertSame('calibration_insuffisante', $r->methode);
    }
    public function test_rssi_instable_reduit_la_confiance(): void
    {
        $stable = $this->svc()->classify(['a' => [-46], 'b' => [-58], 'c' => [-67]], $this->fps());
        $instable = $this->svc()->classify(['a' => [-46, -75, -44, -78, -45], 'b' => [-58], 'c' => [-67]], $this->fps());
        $this->assertTrue($instable->instable); $this->assertLessThan($stable->confiance, $instable->confiance);
    }
    public function test_un_seul_ap_confiance_plafonnee(): void
    {
        $r = $this->svc()->classify(['a' => [-46]], $this->fps());
        $this->assertSame('ap_seul', $r->methode); $this->assertSame('moyen', $r->assurance); $this->assertLessThanOrEqual(0.65, $r->confiance);
    }
}
