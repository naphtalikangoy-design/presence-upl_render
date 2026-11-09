<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{JournalAudit, PointAcces};
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** CRUD générique piloté par config/admin_resources.php (facultés, filières, promotions, entreprises, bâtiments, AP, horaires). */
class ResourceController extends Controller
{
    private function cfg(string $slug): array { $c = config("admin_resources.$slug"); abort_unless($c, 404); return $c; }

    private function options(array $c): array
    {
        $o = [];
        foreach ($c['champs'] as $f) if ($f[2] === 'select') $o[$f[0]] = isset($f[4]['model']) ? $f[4]['model']::orderBy($f[4]['label'])->pluck($f[4]['label'], 'id')->all() : $f[4];
        return $o;
    }

    public function index(string $slug)
    {
        $c = $this->cfg($slug);
        $rows = $c['model']::with($c['with'] ?? [])->orderByDesc('id')->paginate(25);
        return view('admin.crud.index', ['c' => $c, 'slug' => $slug, 'rows' => $rows, 'opts' => $this->options($c)]);
    }

    public function store(Request $r, string $slug)
    {
        $c = $this->cfg($slug); $d = $this->data($r, $c, $slug, null);
        $m = $c['model']::create($d);
        JournalAudit::noter("admin.$slug.creation", $r, $m, $d);
        return redirect("/admin/$slug")->with('ok', $c['singulier'] . ' créé(e).');
    }

    public function edit(string $slug, int $id)
    {
        $c = $this->cfg($slug);
        return view('admin.crud.edit', ['c' => $c, 'slug' => $slug, 'm' => $c['model']::findOrFail($id), 'opts' => $this->options($c)]);
    }

    public function update(Request $r, string $slug, int $id)
    {
        $c = $this->cfg($slug); $m = $c['model']::findOrFail($id); $d = $this->data($r, $c, $slug, $id);
        $avant = $m->only(array_keys($d)); $m->update($d);
        JournalAudit::noter("admin.$slug.modification", $r, $m, ['avant' => $avant, 'apres' => $d]);
        return redirect("/admin/$slug")->with('ok', $c['singulier'] . ' modifié(e).');
    }

    public function destroy(Request $r, string $slug, int $id)
    {
        $c = $this->cfg($slug); $m = $c['model']::findOrFail($id);
        try { $m->delete(); }
        catch (QueryException $e) { return back()->with('err', 'Suppression impossible : cet élément est utilisé ailleurs. Désactivez-le plutôt.'); }
        JournalAudit::noter("admin.$slug.suppression", $r, $m);
        return back()->with('ok', $c['singulier'] . ' supprimé(e).');
    }

    private function data(Request $r, array $c, string $slug, ?int $id): array
    {
        if ($slug === 'points-acces' && $r->filled('identifiant')) {
            $n = PointAcces::normalize($r->input('identifiant'));
            if (!$n) throw ValidationException::withMessages(['identifiant' => 'Adresse MAC/BSSID invalide (ex. aa:bb:cc:dd:ee:ff).']);
            $r->merge(['identifiant' => $n]);
        }
        $rules = [];
        foreach ($c['champs'] as $f) $rules[$f[0]] = str_replace('{id}', (string) ($id ?? 'NULL'), $f[3]);
        $d = $r->validate($rules);
        foreach ($c['champs'] as $f) {
            if ($f[2] === 'checkbox') $d[$f[0]] = $r->boolean($f[0]);
            if ($f[2] === 'json') $d[$f[0]] = filled($d[$f[0]] ?? null) ? json_decode($d[$f[0]], true) : null;
            if ($f[2] === 'time' && isset($d[$f[0]])) $d[$f[0]] = substr($d[$f[0]], 0, 5);
        }
        if ($slug === 'horaires') {
            $o = [$d['fenetre_debut'], $d['heure_arrivee'], $d['heure_limite'], $d['heure_depart'], $d['fenetre_fin']];
            $s = $o; sort($s);
            if ($o !== $s) throw ValidationException::withMessages(['fenetre_debut' => 'Ordre attendu : ouverture ≤ arrivée ≤ limite ≤ départ ≤ fermeture.']);
        }
        return $d;
    }
}
