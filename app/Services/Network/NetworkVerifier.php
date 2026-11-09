<?php
namespace App\Services\Network;

use App\Models\User;
use Illuminate\Http\Request;

interface NetworkVerifier
{
    /** Vérifie côté SERVEUR que l'utilisateur a une session réseau valide sur le Wi-Fi UPL. */
    public function verify(User $user, Request $request): NetworkCheckResult;
}
