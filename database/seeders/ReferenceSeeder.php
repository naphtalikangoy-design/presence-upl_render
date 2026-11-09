<?php
namespace Database\Seeders;
use App\Models\{Faculte, Filiere, Horaire, JourOuvrable, Promotion};
use Illuminate\Database\Seeder;
class ReferenceSeeder extends Seeder {
    public function run(): void {
        // EXEMPLES À REMPLACER depuis /admin/facultes, /admin/filieres, /admin/promotions (listes officielles de l'UPL).
        foreach (['Faculté (exemple 1)' => ['Filière (exemple A)', 'Filière (exemple B)'], 'Faculté (exemple 2)' => ['Filière (exemple C)']] as $f => $filieres) {
            $fac = Faculte::firstOrCreate(['nom' => $f]);
            foreach ($filieres as $fi) Filiere::firstOrCreate(['faculte_id' => $fac->id, 'nom' => $fi]);
        }
        foreach (['L1', 'L2', 'L3', 'M1', 'M2'] as $p) Promotion::firstOrCreate(['nom' => $p]);
        foreach (range(1, 7) as $j) JourOuvrable::firstOrCreate(['jour_semaine' => $j], ['actif' => $j <= 5]);
        if (!Horaire::exists()) Horaire::create(['nom' => 'Horaire standard', 'fenetre_debut' => '06:30', 'heure_arrivee' => '08:00',
            'heure_limite' => '08:15', 'heure_depart' => '16:00', 'fenetre_fin' => '17:30', 'actif' => true]);
    }
}
