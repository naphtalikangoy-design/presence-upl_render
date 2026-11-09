<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    // 'role' et 'statut' ne sont volontairement PAS mass-assignable (anti-escalade de privilèges)
    protected $fillable = ['matricule', 'name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['password' => 'hashed']; }

    public function stagiaire(): HasOne { return $this->hasOne(Stagiaire::class); }
    public function notifications() { return $this->hasMany(Notification::class); }
    public function sendPasswordResetNotification($token): void
    {
        $url = route('password.reset', ['token' => $token, 'email' => $this->email]);
        \Illuminate\Support\Facades\Mail::raw("Bonjour {$this->name},\n\nPour réinitialiser votre mot de passe UPL PRESENCE : $url\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.",
            fn ($m) => $m->to($this->email)->subject('Réinitialisation du mot de passe — UPL PRESENCE'));
    }
    public function isAdmin(): bool { return $this->role === 'administrateur'; }
    public function isActif(): bool { return $this->statut === 'actif'; }
}
