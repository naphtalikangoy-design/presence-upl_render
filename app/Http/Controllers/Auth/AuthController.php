<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{JournalAudit, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, RateLimiter};
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }

    public function login(Request $r)
    {
        $c = $r->validate(['matricule' => 'required|string|max:40', 'password' => 'required|string']);
        $key = 'login:' . strtolower($c['matricule']) . '|' . $r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['matricule' => 'Trop de tentatives. Réessayez dans ' . RateLimiter::availableIn($key) . ' s.']);
        }
        if (!Auth::attempt(['matricule' => $c['matricule'], 'password' => $c['password']], $r->boolean('remember'))) {
            RateLimiter::hit($key, 300);
            JournalAudit::noter('auth.echec', $r, null, ['matricule' => $c['matricule']]);
            throw ValidationException::withMessages(['matricule' => 'Matricule ou mot de passe incorrect.']);
        }
        if (in_array(Auth::user()->statut, ['rejete', 'desactive'], true)) {
            Auth::logout(); $r->session()->invalidate();
            throw ValidationException::withMessages(['matricule' => 'Ce compte est désactivé ou rejeté.']);
        }
        RateLimiter::clear($key); $r->session()->regenerate();
        return redirect()->intended(Auth::user()->isAdmin() ? '/admin/dashboard' : '/stagiaire/dashboard');
    }

    public function logout(Request $r)
    {
        Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken();
        return redirect('/connexion');
    }
}
