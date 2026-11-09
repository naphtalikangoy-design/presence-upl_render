<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Batiment, Faculte, JournalAudit, Presence, Stagiaire};
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function index(Request $r)
    {
        $q = Presence::with('stagiaire.user', 'stagiaire.faculte', 'batiment')
            ->when($r->filled('date_debut'), fn ($w) => $w->where('date', '>=', $r->date_debut))
            ->when($r->filled('date_fin'), fn ($w) => $w->where('date', '<=', $r->date_fin))
            ->when($r->filled('batiment_id'), fn ($w) => $w->where('batiment_id', $r->batiment_id))
            ->when($r->filled('statut'), fn ($w) => $w->where('statut', $r->statut))
            ->when($r->filled('type'), fn ($w) => $w->where('type', $r->type))
            ->when($r->filled('faculte_id'), fn ($w) => $w->whereHas('stagiaire', fn ($s) => $s->where('faculte_id', $r->faculte_id)))
            ->when($r->filled('q'), fn ($w) => $w->whereHas('stagiaire', fn ($s) => $s->where(fn ($x) => $x->whereHas('user', fn ($u) => $u->where('matricule', 'like', '%' . $r->q . '%'))
                ->orWhere('nom', 'like', '%' . $r->q . '%')->orWhere('prenom', 'like', '%' . $r->q . '%'))));
        return view('admin.presences.index', ['rows' => $q->latest('pointe_a')->paginate(30)->withQueryString(), 'facultes' => Faculte::orderBy('nom')->get(), 'batiments' => Batiment::orderBy('nom')->get()]);
    }

    public function edit(Presence $presence)
    {
        $presence->load('stagiaire.user', 'batiment', 'pointAcces');
        $hist = JournalAudit::with('user')->where('cible_type', 'Presence')->where('cible_id', $presence->id)->latest()->get();
        return view('admin.presences.edit', ['p' => $presence, 'hist' => $hist]);
    }

    public function update(Request $r, Presence $presence)
    {
        $d = $r->validate(['statut' => 'required|in:present,retard,absent', 'retard_minutes' => 'required|integer|min:0|max:600',
            'pointe_a' => 'required|date', 'justification' => 'required|string|min:5|max:1000']);
        $avant = $presence->only(['statut', 'retard_minutes', 'pointe_a', 'date']);
        $at = Carbon::parse($d['pointe_a']);
        try {
            $presence->update(['statut' => $d['statut'], 'retard_minutes' => $d['statut'] === 'retard' ? $d['retard_minutes'] : 0, 'pointe_a' => $at,
                'date' => $at->toDateString(), 'justification' => $d['justification'], 'corrige' => true]);
        } catch (QueryException $e) { return back()->withInput()->with('err', 'Une présence du même type existe déjà à cette date.'); }
        JournalAudit::noter('admin.presence.correction', $r, $presence, ['avant' => $avant, 'apres' => $presence->only(['statut', 'retard_minutes', 'pointe_a', 'date']), 'justification' => $d['justification']]);
        return redirect("/admin/presences/{$presence->id}/edit")->with('ok', 'Présence corrigée et historisée.');
    }

    public function create() { return view('admin.presences.create', ['batiments' => Batiment::orderBy('nom')->get()]); }

    public function store(Request $r)
    {
        $d = $r->validate(['matricule' => 'required|exists:users,matricule', 'type' => 'required|in:arrivee,depart', 'pointe_a' => 'required|date',
            'statut' => 'required|in:present,retard', 'retard_minutes' => 'nullable|integer|min:0|max:600', 'batiment_id' => 'nullable|exists:batiments,id', 'justification' => 'required|string|min:5|max:1000']);
        $st = Stagiaire::whereHas('user', fn ($u) => $u->where('matricule', $d['matricule']))->first();
        if (!$st) return back()->withInput()->with('err', 'Ce matricule n\'est pas celui d\'un stagiaire.');
        $at = Carbon::parse($d['pointe_a']);
        try {
            $p = Presence::create(['stagiaire_id' => $st->id, 'batiment_id' => $d['batiment_id'] ?? null, 'date' => $at->toDateString(), 'type' => $d['type'], 'pointe_a' => $at,
                'latitude' => 0, 'longitude' => 0, 'precision_gps' => 0, 'distance_m' => 0, 'statut' => $d['statut'], 'retard_minutes' => $d['statut'] === 'retard' ? ($d['retard_minutes'] ?? 0) : 0,
                'wifi_verifie' => false, 'wifi_source' => 'manuel', 'corrige' => true, 'justification' => $d['justification']]);
        } catch (QueryException $e) { return back()->withInput()->with('err', 'Une présence du même type existe déjà à cette date.'); }
        JournalAudit::noter('admin.presence.saisie_manuelle', $r, $p, ['justification' => $d['justification']]);
        return redirect("/admin/presences/{$p->id}/edit")->with('ok', 'Présence ajoutée manuellement (source « manuel »).');
    }
}
