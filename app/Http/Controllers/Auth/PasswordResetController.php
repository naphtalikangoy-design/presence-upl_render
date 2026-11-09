<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PwRule;

class PasswordResetController extends Controller
{
    public function forgot() { return view('auth.forgot'); }

    public function send(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        Password::sendResetLink($r->only('email'));
        // Réponse identique que l'e-mail existe ou non (pas d'énumération de comptes)
        return back()->with('ok', 'Si un compte correspond à cette adresse, un lien de réinitialisation vient d\'être envoyé.');
    }

    public function form(Request $r, string $token) { return view('auth.reset', ['token' => $token, 'email' => $r->query('email')]); }

    public function reset(Request $r)
    {
        $d = $r->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PwRule::min(8)->letters()->numbers()]]);
        $status = Password::reset($d, function ($user, $pw) {
            $user->forceFill(['password' => $pw])->setRememberToken(Str::random(60));
            $user->save();
        });
        return $status === Password::PASSWORD_RESET
            ? redirect('/connexion')->with('ok', 'Mot de passe modifié. Vous pouvez vous connecter.')
            : back()->withErrors(['email' => 'Lien invalide ou expiré.']);
    }
}
