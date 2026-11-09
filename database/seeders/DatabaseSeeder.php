<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call(ReferenceSeeder::class);
        if (app()->environment('local', 'testing', 'staging')) $this->call(DemoSeeder::class);   // bâtiment/AP/empreintes SIMULÉS
        if (app()->environment('local')) $this->call(DemoDataSeeder::class);          // comptes + historique de démonstration
        // L'administrateur se crée avec : php artisan upl:creer-admin   (aucun mot de passe fixe dans le projet)
    }
}
