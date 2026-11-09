<?php
namespace Tests\Feature;

use App\Models\{Faculte, Filiere, Promotion, User};
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InscriptionTest extends TestCase
{
    use RefreshDatabase;
    private function data(array $o = []): array
    {
        $this->seed(ReferenceSeeder::class);
        return array_merge(['nom' => 'A', 'postnom' => 'B', 'prenom' => 'C', 'matricule' => 'M100', 'sexe' => 'F', 'faculte_id' => Faculte::first()->id, 'filiere_id' => Filiere::first()->id,
            'promotion_id' => Promotion::first()->id, 'telephone' => '+243970000000', 'email' => 'a@b.test', 'entreprise' => 'Banque X', 'password' => 'Motdepasse1', 'password_confirmation' => 'Motdepasse1'], $o);
    }
    public function test_inscription_hors_liste_officielle_est_en_attente_et_mot_de_passe_hache(): void
    {
        $this->post('/inscription', $this->data())->assertRedirect('/connexion');
        $u = User::where('matricule', 'M100')->first();
        $this->assertSame('en_attente', $u->statut); $this->assertSame('stagiaire', $u->role); $this->assertNotSame('Motdepasse1', $u->password);
        $this->assertTrue(\Hash::check('Motdepasse1', $u->password));
    }
    public function test_matricule_dans_la_liste_officielle_est_actif(): void
    {
        $d = $this->data(); DB::table('matricules_officiels')->insert(['matricule' => 'M100', 'created_at' => now(), 'updated_at' => now()]);
        $this->post('/inscription', $d); $this->assertSame('actif', User::where('matricule', 'M100')->value('statut'));
    }
    public function test_matricule_en_double_refuse(): void
    {
        $d = $this->data(); $this->post('/inscription', $d);
        $this->post('/inscription', $d + ['email' => 'autre@b.test'])->assertSessionHasErrors('matricule');
        $this->assertSame(1, User::where('matricule', 'M100')->count());
    }
    public function test_mot_de_passe_faible_et_champs_manquants_refuses(): void
    {
        $this->post('/inscription', $this->data(['password' => 'abc', 'password_confirmation' => 'abc']))->assertSessionHasErrors('password');
        $this->post('/inscription', $this->data(['nom' => '']))->assertSessionHasErrors('nom');
    }
    public function test_role_ne_peut_pas_etre_force_par_le_formulaire(): void
    {
        $this->post('/inscription', $this->data(['role' => 'administrateur', 'statut' => 'actif']));
        $u = User::where('matricule', 'M100')->first(); $this->assertSame('stagiaire', $u->role); $this->assertSame('en_attente', $u->statut);
    }
}
