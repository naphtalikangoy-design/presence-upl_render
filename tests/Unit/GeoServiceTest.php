<?php
namespace Tests\Unit;
use App\Services\GeoService;
use PHPUnit\Framework\TestCase;

class GeoServiceTest extends TestCase
{
    public function test_distance_nulle(): void { $this->assertEqualsWithDelta(0, GeoService::haversine(-11.68, 27.50, -11.68, 27.50), 0.001); }
    public function test_un_degre_de_latitude_vaut_environ_111_km(): void { $this->assertEqualsWithDelta(111195, GeoService::haversine(0, 0, 1, 0), 100); }
    public function test_environ_50_metres(): void { $this->assertEqualsWithDelta(50, GeoService::haversine(-11.6876, 27.5026, -11.6876 + 50 / 111195, 27.5026), 1.0); }
}
