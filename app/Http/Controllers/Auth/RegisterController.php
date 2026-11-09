<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{EntrepriseAccueil, Faculte, Filiere, JournalAudit, Promotion, Stagiaire, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register', ['facultes' => Faculte::with('filieres')->orderBy('nom')->get(), 'promotions' => Promotion::orderBy('nom')->get(),
            'entreprises' => EntrepriseAccueil::orderBy('nom')->get()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'nom' => 'required|string|max:100', 'postnom' => 'required|string|max:100', 'prenom' => 'required|string|max:100',
            'matricule' => 'required|string|max:40|unique:users,matricule', 'sexe' => 'required|in:M,F',
            'faculte_id' => 'required|exists:facultes,id', 'filiere_id' => 'required|exists:filieres,id', 'promotion_id' => 'required|exists:promotions,id',
            'telephone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s().-]{6,30}$/'], 'email' => 'required|email|max:255|unique:users,email',
            'entreprise' => 'required|string|max:255', 'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], ['matricule.unique' => 'Ce matricule possède déjà un compte.']);
        if (!Filiere::where('id', $d['filiere_id'])->where('faculte_id', $d['faculte_id'])->exists())
            throw ValidationException::withMessages(['filiere_id' => 'Cette filière n\'appartient pas à la faculté choisie.']);

        // Vérification avec la liste officielle (si l'administration en a fourni une)
        $officiel = DB::table('matricules_officiels')->where('matricule', $d['matricule'])->exists();
        $statut = $officiel ? 'actif' : 'en_attente';

        DB::transaction(function () use ($d, $statut, $r) {
            $u = new User(['matricule' => $d['matricule'], 'name' => trim("{$d['nom']} {$d['postnom']} {$d['prenom']}"), 'email' => $d['email'], 'password' => $d['password']]);
            $u->role = 'stagiaire'; $u->statut = $statut; $u->save();
            $e = EntrepriseAccueil::firstOrCreate(['nom' => trim($d['entreprise'])]);
            Stagiaire::create(['user_id' => $u->id, 'nom' => $d['nom'], 'postnom' => $d['postnom'], 'prenom' => $d['prenom'], 'sexe' => $d['sexe'],
                'faculte_id' => $d['faculte_id'], 'filiere_id' => $d['filiere_id'], 'promotion_id' => $d['promotion_id'], 'telephone' => $d['telephone'], 'entreprise_id' => $e->id]);
            $u->notifications()->create(['titre' => 'Bienvenue', 'message' => $statut === 'actif' ? 'Votre compte est actif.' : 'Votre compte est en attente de validation par l\'administration.']);
            JournalAudit::noter('auth.inscription', $r, $u, ['statut' => $statut, 'matricule_officiel' => $officiel]);
        });
        return redirect('/connexion')->with('ok', $officiel
            ? 'Inscription réussie. Vous pouvez vous connecter avec votre matricule.'
            : 'Inscription enregistrée. Votre compte sera activé après validation par l\'administration ; vous pouvez déjà vous connecter.');
    }
}
