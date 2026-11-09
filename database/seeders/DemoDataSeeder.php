<?php
namespace Database\Seeders;

use App\Models\{Batiment, Faculte, Filiere, JourOuvrable, Presence, Promotion, Stagiaire, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** DONNÉES DE DÉMONSTRATION (environnement local uniquement). Mots de passe aléatoires, affichés une seule fois. */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $fac = Faculte::first(); $fil = Filiere::where('faculte_id', $fac->id)->first(); $pro = Promotion::first(); $bat = Batiment::first();
        $ouv = JourOuvrable::where('actif', true)->pluck('jour_semaine')->all();
        $noms = [['Mukendi', 'Kalala', 'Grâce', 'F'], ['Ilunga', 'Mwamba', 'Patrick', 'M'], ['Kabamba', 'Tshibangu', 'Esther', 'F'], ['Banza', 'Kasongo', 'Jonathan', 'M'], ['Mutombo', 'Ngoie', 'Sarah', 'F'], ['Kyungu', 'Lubaba', 'David', 'M']];
        $this->command?->warn('Comptes de démonstration (mot de passe affiché une seule fois) :');
        foreach ($noms as $i => [$nom, $post, $pre, $sx]) {
            $mat = sprintf('DEMO%03d', $i + 1);
            if (User::where('matricule', $mat)->exists()) continue;
            $pw = Str::password(12, symbols: false);
            $u = new User(['matricule' => $mat, 'name' => "$nom $post $pre", 'email' => strtolower($mat) . '@demo.invalid', 'password' => $pw]);
            $u->role = 'stagiaire'; $u->statut = $i === 5 ? 'en_attente' : 'actif'; $u->save();
            $s = Stagiaire::create(['user_id' => $u->id, 'nom' => $nom, 'postnom' => $post, 'prenom' => $pre, 'sexe' => $sx, 'faculte_id' => $fac->id, 'filiere_id' => $fil->id,
                'promotion_id' => $pro->id, 'telephone' => '+243000000' . str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'date_debut_stage' => now()->subDays(30)->toDateString()]);
            $this->command?->line("  $mat / $pw");
            if ($u->statut !== 'actif') continue;
            for ($d = 20; $d >= 1; $d--) {
                $day = now()->subDays($d);
                if (!in_array($day->dayOfWeekIso, $ouv) || random_int(1, 10) === 1) continue;
                $retard = random_int(1, 5) === 1 ? random_int(5, 40) : 0;
                $at = $day->copy()->setTime(8, 0)->addMinutes($retard ? 15 + $retard : random_int(-25, 10));
                $base = ['stagiaire_id' => $s->id, 'batiment_id' => $bat?->id, 'date' => $day->toDateString(), 'latitude' => 0, 'longitude' => 0, 'precision_gps' => 0, 'distance_m' => 0,
                    'wifi_verifie' => false, 'wifi_source' => 'demo'];
                Presence::create($base + ['type' => 'arrivee', 'pointe_a' => $at, 'statut' => $retard ? 'retard' : 'present', 'retard_minutes' => $retard]);
                Presence::create($base + ['type' => 'depart', 'pointe_a' => $day->copy()->setTime(16, random_int(0, 40)), 'statut' => 'present', 'retard_minutes' => 0]);
            }
        }
    }
}
