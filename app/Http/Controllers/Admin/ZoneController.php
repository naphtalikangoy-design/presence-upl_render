<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Batiment, JournalAudit, ZoneGeographique, ZoneInterieure};
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index(Request $r)
    {
        return view('admin.zones.index', ['zones' => ZoneGeographique::with('batiment')->orderBy('batiment_id')->get(), 'batiments' => Batiment::orderBy('nom')->get(),
            'interieures' => ZoneInterieure::with('batiment')->orderBy('batiment_id')->get(), 'edit' => $r->filled('edit') ? ZoneGeographique::find($r->query('edit')) : null]);
    }

    private function rules(): array
    {
        return ['batiment_id' => 'required|exists:batiments,id', 'nom' => 'required|max:255', 'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180', 'rayon_m' => 'required|integer|between:5,2000', 'actif' => 'boolean'];
    }

    public function store(Request $r)
    {
        $d = $r->validate($this->rules()); $d['actif'] = $r->boolean('actif');
        $z = ZoneGeographique::create($d); JournalAudit::noter('admin.zone.creation', $r, $z, $d);
        return redirect('/admin/zones')->with('ok', 'Zone GPS créée.');
    }

    public function update(Request $r, ZoneGeographique $zone)
    {
        $d = $r->validate($this->rules()); $d['actif'] = $r->boolean('actif');
        $avant = $zone->only(array_keys($d)); $zone->update($d);
        JournalAudit::noter('admin.zone.modification', $r, $zone, ['avant' => $avant, 'apres' => $d]);
        return redirect('/admin/zones')->with('ok', 'Zone GPS modifiée.');
    }

    public function destroy(Request $r, ZoneGeographique $zone)
    {
        JournalAudit::noter('admin.zone.suppression', $r, $zone); $zone->delete();
        return redirect('/admin/zones')->with('ok', 'Zone supprimée.');
    }

    public function storeInterieure(Request $r)
    {
        $d = $r->validate(['batiment_id' => 'required|exists:batiments,id', 'nom' => 'required|max:255', 'etage' => 'nullable|max:20']);
        $d['autorise'] = $r->boolean('autorise');
        $z = ZoneInterieure::create($d); JournalAudit::noter('admin.zone_interieure.creation', $r, $z, $d);
        return redirect('/admin/zones')->with('ok', 'Zone intérieure enregistrée.');
    }

    public function destroyInterieure(Request $r, ZoneInterieure $zi)
    {
        JournalAudit::noter('admin.zone_interieure.suppression', $r, $zi); $zi->delete();
        return redirect('/admin/zones')->with('ok', 'Zone intérieure supprimée (les empreintes liées restent mais sans zone).');
    }
}
