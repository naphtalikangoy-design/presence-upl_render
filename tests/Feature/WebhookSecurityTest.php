<?php
namespace Tests\Feature;

use App\Models\SessionReseau;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;
    private function send(array $body, ?string $sig = null, ?int $ts = null)
    {
        $ts ??= time(); $raw = json_encode($body);
        $sig ??= hash_hmac('sha256', $ts . '.' . $raw, 'secret-de-test');
        return $this->call('POST', '/api/network/events', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_UPL_TIMESTAMP' => (string) $ts, 'HTTP_X_UPL_SIGNATURE' => $sig], $raw);
    }
    protected function setUp(): void { parent::setUp(); config(['upl.webhook_secret' => 'secret-de-test']); }

    public function test_signature_valide_cree_une_session(): void
    {
        $this->send(['event' => 'session_start', 'username' => 'T001', 'ip' => '10.0.0.5', 'ap_mac' => 'AA:BB:CC:DD:EE:FF', 'ssid' => 'UPL-WIFI'])->assertOk();
        $this->assertDatabaseHas('sessions_reseau', ['username' => 'T001', 'ap_identifiant' => 'aabbccddeeff', 'est_demo' => false]);
    }
    public function test_signature_invalide_refusee(): void
    {
        $this->send(['event' => 'session_start', 'username' => 'T001'], 'deadbeef')->assertStatus(401);
        $this->assertSame(0, SessionReseau::count());
    }
    public function test_horodatage_trop_ancien_refuse(): void
    {
        $this->send(['event' => 'session_start', 'username' => 'T001'], null, time() - 3600)->assertStatus(401);
    }
}
