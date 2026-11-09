<?php
namespace App\Console\Commands;

use App\Models\{JournalAudit, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreerAdmin extends Command
{
    protected $signature = 'upl:creer-admin {--matricule=} {--nom=} {--email=} {--env : Lit UPL_ADMIN_* (déploiement) et ne crée rien si un administrateur existe déjà}';
    protected $description = 'Crée un compte administrateur (mot de passe saisi de façon masquée ou fourni par variable d\'environnement ; jamais dans le code).';

    public function handle(): int
    {
        if ($this->option('env')) {
            if (User::where('role', 'administrateur')->exists()) { $this->info('Un administrateur existe déjà : rien à faire.'); return 0; }
            $c = config('upl.admin_bootstrap');
            if (!$c['matricule'] || !$c['email'] || !$c['password']) { $this->warn('Variables UPL_ADMIN_MATRICULE / EMAIL / PASSWORD absentes : aucun administrateur créé.'); return 0; }
            $d = ['matricule' => $c['matricule'], 'name' => $c['nom'], 'email' => $c['email'], 'password' => $c['password']];
        } else {
            $d = [
                'matricule' => $this->option('matricule') ?: $this->ask('Matricule / identifiant administrateur'),
                'name' => $this->option('nom') ?: $this->ask('Nom complet'),
                'email' => $this->option('email') ?: $this->ask('E-mail'),
                'password' => $this->secret('Mot de passe (12 caractères min.)'),
            ];
            if ($d['password'] !== $this->secret('Confirmez le mot de passe')) { $this->error('Les mots de passe diffèrent.'); return 1; }
        }
        $v = Validator::make($d, ['matricule' => 'required|max:40|unique:users', 'name' => 'required|max:255', 'email' => 'required|email|unique:users',
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()]]);
        if ($v->fails()) { foreach ($v->errors()->all() as $e) $this->error($e); return 1; }
        $u = new User($d); $u->role = 'administrateur'; $u->statut = 'actif'; $u->save();
        JournalAudit::create(['user_id' => $u->id, 'action' => 'admin.cree_par_cli', 'cible_type' => 'User', 'cible_id' => $u->id]);
        $this->info("Administrateur {$u->matricule} créé.");
        return 0;
    }
}
