<?php
namespace App\Http\Controllers\Stagiaire;

use App\Http\Controllers\Controller;
use App\Models\{JourOuvrable, Presence};
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $r)
    {
        $u = $r->user(); $st = $u->stagiaire->load('faculte', 'filiere', 'promotion', 'entreprise');
        $today = now()->toDateString();
        $arr = Presence::with('batiment')->where('stagiaire_id', $st->id)->whereDate('date', $today)->where('type', 'arrivee')->first();
        $dep = Presence::where('stagiaire_id', $st->id)->whereDate('date', $today)->where('type', 'depart')->first();
        $ouvrable = JourOuvrable::where('jour_semaine', now()->dayOfWeekIso)->where('actif', true)->exists();
        $mois = Presence::where('stagiaire_id', $st->id)->where('type', 'arrivee')->where('date', '>=', now()->startOfMonth()->toDateString());
        return view('stagiaire.dashboard', ['u' => $u, 'st' => $st, 'arr' => $arr, 'dep' => $dep, 'ouvrable' => $ouvrable,
            'presMois' => (clone $mois)->count(), 'retMois' => (clone $mois)->where('statut', 'retard')->count(),
            'notifs' => $u->notifications()->latest()->limit(3)->get()]);
    }
}
