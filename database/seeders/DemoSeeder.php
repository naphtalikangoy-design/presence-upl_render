<?php
namespace Database\Seeders;
use App\Models\{Batiment, EmpreinteWifi, PointAcces, ZoneGeographique, ZoneInterieure};
use Illuminate\Database\Seeder;

/** DONNÉES DE DÉMONSTRATION (SIMULÉES). Coordonnées = PLACEHOLDER à remplacer depuis /admin/zones. */
class DemoSeeder extends Seeder {
    public function run(): void {
        $b = Batiment::firstOrCreate(['nom' => 'IAGE (DÉMO)'], ['code' => 'IAGE', 'actif' => true, 'pointage_actif' => true]);
        ZoneGeographique::firstOrCreate(['batiment_id' => $b->id, 'nom' => 'Périmètre IAGE (placeholder)'], ['latitude' => -11.6876000, 'longitude' => 27.5026000, 'rayon_m' => 50, 'actif' => true]);
        $z = ZoneInterieure::firstOrCreate(['batiment_id' => $b->id, 'nom' => 'Rez-de-chaussée (démo)'], ['etage' => '0', 'autorise' => true]);
        $aps = [];
        foreach ([1, 2, 3] as $i) $aps[$i] = PointAcces::firstOrCreate(['identifiant' => sprintf('02000000000%d', $i)],
            ['batiment_id' => $b->id, 'nom' => "AP-DEMO-$i", 'marque' => 'DEMO', 'actif' => true, 'etage' => '0']);
        if (EmpreinteWifi::where('batiment_id', $b->id)->doesntExist()) {
            $ids = array_map(fn ($a) => $a->identifiant, array_values($aps));
            $noise = [-2, 1, 3, -1, 2, 0];
            foreach ($noise as $n) {
                EmpreinteWifi::create(['batiment_id' => $b->id, 'zone_interieure_id' => $z->id, 'classe' => 'interieur', 'est_demo' => true, 'releve_at' => now(), 'nb_echantillons' => 10,
                    'signature' => [$ids[0] => -45 + $n, $ids[1] => -57 - $n, $ids[2] => -68 + $n]]);
                EmpreinteWifi::create(['batiment_id' => $b->id, 'classe' => 'exterieur', 'est_demo' => true, 'releve_at' => now(), 'nb_echantillons' => 10,
                    'signature' => [$ids[0] => -76 + $n, $ids[1] => -83 - $n, $ids[2] => -89 + $n]]);
            }
        }
    }
}
