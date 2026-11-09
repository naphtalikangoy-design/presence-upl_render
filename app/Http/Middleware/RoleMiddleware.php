<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $u = $request->user();
        abort_unless($u && $u->role === $role && in_array($u->statut, ['actif', 'en_attente'], true), 403, 'Accès refusé.');
        abort_if($role === 'stagiaire' && !$u->stagiaire, 403, 'Profil stagiaire incomplet : contactez l\'administration.');
        return $next($request);
    }
}
