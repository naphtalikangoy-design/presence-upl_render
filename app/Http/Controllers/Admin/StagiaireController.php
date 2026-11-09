<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{EntrepriseAccueil, Faculte, Filiere, JournalAudit, Presence, Promotion, Stagiaire, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class StagiaireController extends Controller
{
    private function refs(): array
    {
        return ['facultes' => Faculte::orderBy('nom')->get(), 'filieres' => Filiere::orderBy('nom')->get(), 'promotions' => Promotion::orderBy('nom')->get(), 'entreprises' => EntrepriseAccueil::orderBy('nom')->get()];
    }

    public function index(Request $r)
    {
        $q = Stagiaire::with('user', 'faculte', 'filiere', 'promotion')
            ->when($r->filled('q'), fn ($w) => $w->where(fn ($x) => $x->whereHas('user', fn ($u) => $u->where('matricule', 'like', '%' . $r->q . '%'))
                ->orWhere('nom', 'like', '%' . $r->q . '%')->orWhere('postnom', 'like', '%' . $r->q . '%')->orWhere('prenom', 'like', '%' . $r->q . '%')))
            ->when($r->filled('faculte_id'), fn ($w) => $w->where('faculte_id', $r->faculte_id))
            ->when($r->filled('filiere_id'), fn ($w) => $w->where('filiere_id', $r->filiere_id))
            ->when($r->filled('promotion_id'), fn ($w) => $w->where('promotion_id', $r->promotion_id))
            ->when($r->filled('statut'), fn ($w) => $w->whereHas('user', fn ($u) => $u->where('statut', $r->statut)));
        return view('admin.stagiaires.index', ['rows' => $q->latest('id')->paginate(20)->withQueryString()] + $this->refs());
    }

    public function create() { return view('admin.stagiaires.form', ['st' => null] + $this->refs()); }
    public function edit(Stagiaire $stagiaire) { return view('admin.stagiaires.form', ['st' => $stagiaire->load('user', 'entreprise')] + $this->refs()); }

    public function show(Stagiaire $stagiaire)
    {
        $stagiaire->load('user', 'faculte', 'filiere', 'promotion', 'entreprise');
        $base = Presence::where('stagiaire_id', $stagiaire->id)->where('type', 'arrivee');
        return view('admin.stagiaires.show', ['st' => $stagiaire, 'presences' => Presence::with('batiment')->where('stagiaire_id', $stagiaire->id)->latest('pointe_a')->limit(30)->get(),
            'total' => (clone $base)->count(), 'retards' => (clone $base)->where('statut', 'retard')->count()]);
    }

    private function validated(Request $r, ?Stagiaire $st): array
    {
        $uid = $st?->user_id ?? 'NULL';
        $d = $r->validate([
            'nom' => 'required|max:100', 'postnom' => 'required|max:100', 'prenom' => 'required|max:100', 'matricule' => "required|max:40|unique:users,matricule,$uid",
            'sexe' => 'required|in:M,F', 'faculte_id' => 'required|exists:facultes,id', 'filiere_id' => 'required|exists:filieres,id', 'promotion_id' => 'required|exists:promotions,id',
            'telephone' => ['required', 'max:30', 'regex:/^[0-9+\s().-]{6,30}$/'], 'email' => "required|email|unique:users,email,$uid", 'entreprise' => 'nullable|max:255',
            'date_debut_stage' => 'nullable|date', 'date_fin_stage' => 'nullable|date|after_or_equal:date_debut_stage',
            'password' => [$st ? 'nullable' : 'required', Password::min(8)->letters()->numbers()],
        ]);
        if (!Filiere::where('id', $d['filiere_id'])->where('faculte_id', $d['faculte_id'])->exists())
            throw ValidationException::withMessages(['filiere_id' => 'Cette filière n\'appartient pas à la faculté choisie.']);
        return $d;
    }

    private function save(array $d, ?Stagiaire $st): Stagiaire
    {
        return DB::transaction(function () use ($d, $st) {
            $u = $st?->user ?? new User();
            $u->fill(['matricule' => $d['matricule'], 'name' => trim("{$d['nom']} {$d['postnom']} {$d['prenom']}"), 'email' => $d['email']]);
            if (!empty($d['password'])) $u->password = $d['password'];
            if (!$st) { $u->role = 'stagiaire'; $u->statut = 'actif'; }
            $u->save();
            $e = !empty($d['entreprise']) ? EntrepriseAccueil::firstOrCreate(['nom' => trim($d['entreprise'])]) : null;
            return Stagiaire::updateOrCreate(['user_id' => $u->id], ['nom' => $d['nom'], 'postnom' => $d['postnom'], 'prenom' => $d['prenom'], 'sexe' => $d['sexe'],
                'faculte_id' => $d['faculte_id'], 'filiere_id' => $d['filiere_id'], 'promotion_id' => $d['promotion_id'], 'telephone' => $d['telephone'],
                'entreprise_id' => $e?->id, 'date_debut_stage' => $d['date_debut_stage'] ?? null, 'date_fin_stage' => $d['date_fin_stage'] ?? null]);
        });
    }

    public function store(Request $r)
    {
        $st = $this->save($this->validated($r, null), null);
        JournalAudit::noter('admin.stagiaire.creation', $r, $st);
        return redirect("/admin/stagiaires/{$st->id}")->with('ok', 'Stagiaire créé.');
    }

    public function update(Request $r, Stagiaire $stagiaire)
    {
        $this->save($this->validated($r, $stagiaire), $stagiaire);
        JournalAudit::noter('admin.stagiaire.modification', $r, $stagiaire);
        return redirect("/admin/stagiaires/{$stagiaire->id}")->with('ok', 'Fiche mise à jour.');
    }

    private function statut(Request $r, Stagiaire $s, string $statut, string $msg, string $action)
    {
        $s->user->statut = $statut; $s->user->save();
        JournalAudit::noter($action, $r, $s);
        $s->user->notifications()->create(['titre' => 'Statut du compte', 'message' => $msg]);
        return back()->with('ok', 'Statut mis à jour : ' . str_replace('_', ' ', $statut) . '.');
    }
    public function valider(Request $r, Stagiaire $stagiaire) { return $this->statut($r, $stagiaire, 'actif', 'Votre compte a été validé. Vous pouvez effectuer votre pointage.', 'admin.stagiaire.validation'); }
    public function rejeter(Request $r, Stagiaire $stagiaire) { return $this->statut($r, $stagiaire, 'rejete', 'Votre inscription a été rejetée. Contactez l\'administration.', 'admin.stagiaire.rejet'); }
    public function reactiver(Request $r, Stagiaire $stagiaire) { return $this->statut($r, $stagiaire, 'actif', 'Votre compte a été réactivé.', 'admin.stagiaire.reactivation'); }

    public function destroy(Request $r, Stagiaire $stagiaire)
    {
        if ($r->boolean('definitif')) {
            JournalAudit::noter('admin.stagiaire.suppression', $r, $stagiaire, ['matricule' => $stagiaire->user->matricule]);
            $stagiaire->user->delete();
            return redirect('/admin/stagiaires')->with('ok', 'Compte et données supprimés définitivement.');
        }
        return $this->statut($r, $stagiaire, 'desactive', 'Votre compte a été désactivé.', 'admin.stagiaire.desactivation');
    }
}
