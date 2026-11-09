<?php
namespace Tests\Feature;

use App\Models\{Batiment, EmpreinteWifi, Faculte, Filiere, Presence, Promotion, Stagiaire, User, ZoneGeographique};
use App\Services\Wifi\{TxPowerService, UnsupportedOperationException};
use Database\Seeders\{DemoSeeder, ReferenceSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ReferenceSeeder::class, DemoSeeder::class]);
        $this->admin = new User(['matricule' => 'ADM', 'name' => 'Admin', 'email' => 'adm@x.test', 'password' => 'Secret#12345']);
        $this->admin->role = 'administrateur'; $this->admin->statut = 'actif'; $this->admin->save();
    }

    public function test_toutes_les_pages_admin_s_affichent(): void
    {
        foreach (['dashboard', 'stagiaires', 'stagiaires/create', 'presences', 'presences/create', 'facultes', 'filieres', 'promotions', 'entreprises', 'batiments', 'points-acces', 'horaires', 'zones',
                  'configuration-reseau', 'mesures-rssi', 'connexions', 'etat-reseau', 'calibration', 'tests-localisation', 'puissance', 'rapports', 'rapports/imprimer', 'journaux', 'parametres'] as $p)
            $this->actingAs($this->admin)->get("/admin/$p")->assertSuccessful();
    }
    public function test_crud_batiment_et_point_acces_normalise_la_mac(): void
    {
        $this->actingAs($this->admin)->post('/admin/batiments', ['nom' => 'Bloc Z', 'code' => 'Z', 'actif' => 1, 'pointage_actif' => 1])->assertRedirect('/admin/batiments');
        $b = Batiment::where('nom', 'Bloc Z')->first(); $this->assertNotNull($b);
        $this->actingAs($this->admin)->post('/admin/points-acces', ['batiment_id' => $b->id, 'nom' => 'AP-Z1', 'identifiant' => 'AA:BB:CC:DD:EE:01', 'actif' => 1])->assertRedirect('/admin/points-acces');
        $this->assertDatabaseHas('points_acces', ['identifiant' => 'aabbccddee01']);
        $this->actingAs($this->admin)->post('/admin/points-acces', ['batiment_id' => $b->id, 'nom' => 'X', 'identifiant' => 'pas-une-mac'])->assertSessionHasErrors('identifiant');
    }
    public function test_zone_gps_validation_des_coordonnees(): void
    {
        $b = Batiment::first();
        $this->actingAs($this->admin)->post('/admin/zones', ['batiment_id' => $b->id, 'nom' => 'Z', 'latitude' => 95, 'longitude' => 27, 'rayon_m' => 50])->assertSessionHasErrors('latitude');
        $this->actingAs($this->admin)->post('/admin/zones', ['batiment_id' => $b->id, 'nom' => 'Z2', 'latitude' => -11.7, 'longitude' => 27.5, 'rayon_m' => 30, 'actif' => 1])->assertRedirect('/admin/zones');
        $this->assertSame(30, ZoneGeographique::where('nom', 'Z2')->value('rayon_m'));
    }
    public function test_horaire_incoherent_refuse(): void
    {
        $this->actingAs($this->admin)->post('/admin/horaires', ['nom' => 'X', 'fenetre_debut' => '09:00', 'heure_arrivee' => '08:00', 'heure_limite' => '08:15', 'heure_depart' => '16:00', 'fenetre_fin' => '17:00', 'actif' => 1])->assertSessionHasErrors('fenetre_debut');
    }
    public function test_calibration_manuelle_et_rejet_signature_invalide(): void
    {
        $b = Batiment::first(); $n = EmpreinteWifi::count();
        $this->actingAs($this->admin)->post('/admin/calibration', ['batiment_id' => $b->id, 'classe' => 'exterieur', 'signature' => '{"020000000001": -80, "020000000002": -85}'])->assertSessionHas('ok');
        $this->assertSame($n + 1, EmpreinteWifi::count());
        $this->actingAs($this->admin)->post('/admin/calibration', ['batiment_id' => $b->id, 'classe' => 'exterieur', 'signature' => '{"zz": 10}'])->assertSessionHas('err');
    }
    public function test_correction_de_presence_exige_une_justification_et_est_historisee(): void
    {
        $u = new User(['matricule' => 'S1', 'name' => 'S', 'email' => 's@x.test', 'password' => 'Secret#12345']); $u->role = 'stagiaire'; $u->statut = 'actif'; $u->save();
        $st = Stagiaire::create(['user_id' => $u->id, 'nom' => 'A', 'postnom' => 'B', 'prenom' => 'C', 'sexe' => 'M', 'telephone' => '0', 'faculte_id' => Faculte::first()->id, 'filiere_id' => Filiere::first()->id, 'promotion_id' => Promotion::first()->id]);
        $p = Presence::create(['stagiaire_id' => $st->id, 'date' => '2026-11-09', 'type' => 'arrivee', 'pointe_a' => '2026-11-09 08:40:00', 'latitude' => 0, 'longitude' => 0, 'precision_gps' => 0, 'distance_m' => 0, 'statut' => 'retard', 'retard_minutes' => 25]);
        $base = ['statut' => 'present', 'retard_minutes' => 0, 'pointe_a' => '2026-11-09T07:55'];
        $this->actingAs($this->admin)->put("/admin/presences/{$p->id}", $base)->assertSessionHasErrors('justification');
        $this->actingAs($this->admin)->put("/admin/presences/{$p->id}", $base + ['justification' => 'Panne du réseau constatée'])->assertSessionHasNoErrors();
        $p->refresh(); $this->assertSame('present', $p->statut); $this->assertTrue($p->corrige);
        $this->assertDatabaseHas('journaux_audit', ['action' => 'admin.presence.correction', 'cible_id' => $p->id]);
    }
    public function test_modification_de_puissance_jamais_simulee(): void
    {
        config(['upl.network_mode' => 'demo']);
        $this->expectException(UnsupportedOperationException::class);
        app(TxPowerService::class)->apply(\App\Models\PointAcces::first(), ['puissance_dbm' => 10], request());
    }
    public function test_rapport_mensuel_et_export_excel(): void
    {
        $this->actingAs($this->admin)->get('/admin/rapports?type=mois&date=2026-11-09')->assertSuccessful()->assertSee('Rapport mensuel');
    }
}
