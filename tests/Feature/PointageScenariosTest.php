<?php
namespace Tests\Feature;

use App\Models\{Faculte, Filiere, Presence, Promotion, Stagiaire, User};
use Database\Seeders\{DemoSeeder, ReferenceSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointageScenariosTest extends TestCase
{
    use RefreshDatabase;
    private User $u;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->next('Monday')->setTime(8, 10)); // jour ouvrable, avant l'heure limite
        config(['upl.network_mode' => 'demo', 'upl.demo_scenario' => 'interieur']);
        $this->seed([ReferenceSeeder::class, DemoSeeder::class]);
        $this->u = new User(['matricule' => 'T001', 'name' => 'Test Stagiaire', 'password' => 'Secret#12345']);
        $this->u->role = 'stagiaire'; $this->u->statut = 'actif'; $this->u->save();
        Stagiaire::create(['user_id' => $this->u->id, 'nom' => 'A', 'postnom' => 'B', 'prenom' => 'C', 'sexe' => 'M', 'telephone' => '0',
            'faculte_id' => Faculte::first()->id, 'filiere_id' => Filiere::first()->id, 'promotion_id' => Promotion::first()->id]);
    }

    private function pos(array $o = []): array
    {
        return ['position' => array_merge(['latitude' => -11.6876, 'longitude' => 27.5026, 'accuracy' => 15, 'timestamp' => now()->getTimestamp() * 1000], $o)];
    }
    private function tenter(array $pos): \Illuminate\Testing\TestResponse
    {
        $v = $this->actingAs($this->u)->postJson('/stagiaire/pointage/verifier', $pos);
        return $this->actingAs($this->u)->postJson('/stagiaire/pointage/pointer', $pos + ['nonce' => $v->json('nonce')]);
    }

    public function test_A_interieur_pointage_accepte(): void
    {
        $this->tenter($this->pos())->assertOk()->assertJson(['statut' => 'PRESENCE_VALIDEE']);
        $this->assertSame(1, Presence::count());
    }
    public function test_B_exterieur_refuse(): void
    {
        config(['upl.demo_scenario' => 'exterieur']);
        $this->tenter($this->pos())->assertStatus(422)->assertJson(['statut' => 'HORS_DU_BATIMENT']);
        $this->assertSame(0, Presence::count());
    }
    public function test_C_reseau_non_autorise_refuse(): void
    {
        config(['upl.demo_scenario' => 'reseau_non_autorise']);
        $this->tenter($this->pos())->assertStatus(422)->assertJson(['statut' => 'WIFI_NON_AUTORISE']);
    }
    public function test_access_point_inconnu_refuse(): void
    {
        config(['upl.demo_scenario' => 'ap_inconnu']);
        $this->tenter($this->pos())->assertJson(['statut' => 'ACCESS_POINT_NON_AUTORISE']);
    }
    public function test_signal_faible_refuse(): void
    {
        config(['upl.demo_scenario' => 'signal_faible']);
        $this->tenter($this->pos())->assertJson(['statut' => 'SIGNAL_INSUFFISANT']);
    }
    public function test_donnees_reseau_indisponibles_refus_en_mode_strict(): void
    {
        config(['upl.demo_scenario' => 'aucune_donnee']);
        $this->tenter($this->pos())->assertJson(['statut' => 'LOCALISATION_NON_CONFIRMEE']);
    }
    public function test_gps_imprecis_toleré_si_empreinte_multi_ap_confirme(): void
    {
        $this->tenter($this->pos(['accuracy' => 200]))->assertOk()->assertJson(['statut' => 'PRESENCE_VALIDEE']);
        $this->assertSame(0, (int) Presence::first()->wifi_verifie); // démo : jamais "vérifié" réellement
    }
    public function test_gps_imprecis_refuse_si_tolerance_desactivee(): void
    {
        config(['upl.gps.optional_if_fingerprint' => false]);
        $this->tenter($this->pos(['accuracy' => 200]))->assertJson(['statut' => 'GPS_INDISPONIBLE']);
    }
    public function test_gps_hors_zone_refuse_meme_si_wifi_ok(): void
    {
        $this->tenter($this->pos(['latitude' => -11.6700]))->assertJson(['statut' => 'HORS_DU_BATIMENT']);
    }
    public function test_doublon_refuse(): void
    {
        $this->tenter($this->pos())->assertOk();
        $this->tenter($this->pos())->assertJson(['statut' => 'POINTAGE_DEJA_EFFECTUE']);
        $this->assertSame(1, Presence::count());
    }
    public function test_nonce_absent_ou_rejoue_refuse(): void
    {
        $this->actingAs($this->u)->postJson('/stagiaire/pointage/pointer', $this->pos())->assertJson(['statut' => 'DEFI_INVALIDE']);
        $this->assertSame(0, Presence::count());
    }
    public function test_un_stagiaire_ne_peut_pas_acceder_aux_routes_admin_ni_anonyme_au_pointage(): void
    {
        auth()->logout();
        $this->postJson('/stagiaire/pointage/verifier', $this->pos())->assertStatus(401);
    }
}
