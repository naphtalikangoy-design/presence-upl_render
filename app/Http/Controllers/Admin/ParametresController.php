<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ConfigurationReseau, JourOuvrable, JournalAudit};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParametresController extends Controller
{
    public function show()
    {
        return view('admin.parametres', ['jours' => JourOuvrable::pluck('actif', 'jour_semaine')->all(), 'nbOfficiels' => DB::table('matricules_officiels')->count()]);
    }

    public function jours(Request $r)
    {
        $actifs = array_map('intval', (array) $r->input('jours', []));
        foreach (range(1, 7) as $j) JourOuvrable::updateOrCreate(['jour_semaine' => $j], ['actif' => in_array($j, $actifs, true)]);
        JournalAudit::noter('admin.parametres.jours', $r, null, ['jours' => $actifs]);
        return back()->with('ok', 'Jours ouvrables enregistrés.');
    }

    public function regles(Request $r)
    {
        $d = $r->validate(['gps_max_accuracy_m' => 'required|integer|between:5,500', 'rssi_min_rssi_dbm' => 'required|integer|between:-100,-40', 'rssi_min_confidence' => 'required|numeric|between:0.5,0.99',
            'rssi_min_confidence_single' => 'required|numeric|between:0.5,0.99', 'rssi_min_fingerprints_per_class' => 'required|integer|between:3,100', 'max_refused_attempts' => 'required|integer|between:3,50']);
        foreach ($d as $k => $v) ConfigurationReseau::ecrire($k, (string) $v);
        ConfigurationReseau::ecrire('gps_optional_if_fingerprint', $r->boolean('gps_optional_if_fingerprint') ? '1' : '0');
        JournalAudit::noter('admin.parametres.regles', $r, null, $d);
        return back()->with('ok', 'Règles de pointage enregistrées.');
    }

    public function logo(Request $r)
    {
        $r->validate(['logo' => 'required|image|mimes:png|max:1024']);
        $r->file('logo')->move(public_path('uploads'), 'logo.png');
        JournalAudit::noter('admin.parametres.logo', $r);
        return back()->with('ok', 'Logo mis à jour.');
    }

    /** Import de la liste officielle : une ligne par étudiant, « matricule » ou « matricule;nom complet ». */
    public function importer(Request $r)
    {
        $r->validate(['liste' => 'required|string|max:500000']);
        $n = 0;
        foreach (preg_split('/\R/', $r->liste) as $ligne) {
            [$m, $nom] = array_pad(array_map('trim', explode(';', $ligne, 2)), 2, null);
            if ($m === '' || $m === null || mb_strlen($m) > 40) continue;
            DB::table('matricules_officiels')->updateOrInsert(['matricule' => $m], ['nom_complet' => $nom, 'updated_at' => now(), 'created_at' => now()]); $n++;
        }
        JournalAudit::noter('admin.parametres.import_matricules', $r, null, ['lignes' => $n]);
        return back()->with('ok', "$n matricule(s) importé(s) / mis à jour.");
    }

    public function viderListe(Request $r)
    {
        DB::table('matricules_officiels')->delete(); JournalAudit::noter('admin.parametres.vider_matricules', $r);
        return back()->with('ok', 'Liste officielle vidée : les nouvelles inscriptions seront toutes soumises à validation.');
    }
}
