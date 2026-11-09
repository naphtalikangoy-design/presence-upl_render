<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;
    private function user(string $role, string $statut = 'actif'): User
    {
        $u = new User(['matricule' => strtoupper($role) . '1', 'name' => $role, 'email' => "$role@x.test", 'password' => 'Secret#12345']);
        $u->role = $role; $u->statut = $statut; $u->save(); return $u;
    }
    public function test_invite_redirige_vers_connexion(): void { $this->get('/admin/dashboard')->assertRedirect('/connexion'); $this->get('/stagiaire/dashboard')->assertRedirect('/connexion'); }
    public function test_stagiaire_ne_peut_pas_acceder_a_l_admin(): void
    {
        $u = $this->user('stagiaire');
        foreach (['/admin/dashboard', '/admin/stagiaires', '/admin/presences', '/admin/zones', '/admin/configuration-reseau', '/admin/batiments', '/admin/calibration', '/admin/rapports'] as $url)
            $this->actingAs($u)->get($url)->assertForbidden();
        $this->actingAs($u)->post('/admin/configuration-reseau/tester')->assertForbidden();
    }
    public function test_admin_ne_peut_pas_pointer_comme_stagiaire(): void
    {
        $this->actingAs($this->user('administrateur'))->get('/stagiaire/pointage')->assertForbidden();
    }
    public function test_compte_rejete_ou_desactive_est_bloque(): void
    {
        $this->actingAs($this->user('stagiaire', 'desactive'))->get('/stagiaire/dashboard')->assertForbidden();
    }
    public function test_pages_publiques_accessibles(): void
    {
        foreach (['/', '/connexion', '/inscription', '/mot-de-passe-oublie'] as $u) $this->get($u)->assertSuccessful();
    }
    public function test_connexion_par_matricule_et_limitation_des_tentatives(): void
    {
        $this->user('stagiaire');
        $this->post('/connexion', ['matricule' => 'STAGIAIRE1', 'password' => 'faux'])->assertSessionHasErrors('matricule');
        for ($i = 0; $i < 6; $i++) $this->post('/connexion', ['matricule' => 'STAGIAIRE1', 'password' => 'faux']);
        $this->post('/connexion', ['matricule' => 'STAGIAIRE1', 'password' => 'Secret#12345'])->assertSessionHasErrors('matricule'); // verrouillé
    }
}
