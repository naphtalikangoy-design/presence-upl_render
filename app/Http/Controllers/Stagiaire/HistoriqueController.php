<?php
namespace App\Http\Controllers\Stagiaire;

use App\Http\Controllers\Controller;
use App\Models\{JourOuvrable, Presence};
use Carbon\Carbon;
use Illuminate\Http\Request;

class HistoriqueController extends Controller
{
    public function __invoke(Request $r)
    {
        $st = $r->user()->stagiaire; // un stagiaire ne voit QUE ses propres données
        try { $mois = Carbon::createFromFormat('!Y-m', (string) $r->query('mois', now()->format('Y-m')))->startOfMonth(); }
        catch (\Throwable $e) { $mois = now()->startOfMonth(); }

        $rows = Presence::with('batiment')->where('stagiaire_id', $st->id)->whereBetween('date', [$mois->toDateString(), $mois->copy()->endOfMonth()->toDateString()])->orderBy('date', 'desc')->get();
        $jours = $rows->groupBy(fn ($p) => $p->date->toDateString())->map(function ($g) {
            $a = $g->firstWhere('type', 'arrivee'); $d = $g->firstWhere('type', 'depart');
            return ['date' => ($a ?? $d)->date, 'arrivee' => $a?->pointe_a, 'depart' => $d?->pointe_a, 'statut' => $a?->statut ?? 'present', 'retard' => $a?->retard_minutes ?? 0,
                'batiment' => ($a ?? $d)->batiment?->nom, 'duree' => ($a && $d) ? (int) $a->pointe_a->diffInMinutes($d->pointe_a) : null];
        })->sortKeysDesc();

        $ouvrables = JourOuvrable::where('actif', true)->pluck('jour_semaine')->all();
        $debut = $st->date_debut_stage?->toDateString() ?? $st->created_at->toDateString(); $fin = $st->date_fin_stage?->toDateString();
        $cal = []; $today = now()->toDateString();
        for ($d = $mois->copy(); $d->month === $mois->month; $d->addDay()) {
            $ds = $d->toDateString(); $a = $jours->get($ds);
            $cls = match (true) {
                $a !== null => ($a['statut'] === 'retard' ? 'late' : 'ok'),
                !in_array($d->dayOfWeekIso, $ouvrables) || $ds < $debut || ($fin && $ds > $fin) => 'off',
                $ds < $today => 'absent', default => '',
            };
            $cal[] = ['n' => $d->day, 'cls' => $cls, 'today' => $ds === $today];
        }
        return view('stagiaire.historique', ['jours' => $jours, 'cal' => $cal, 'mois' => $mois, 'offset' => $mois->dayOfWeekIso - 1]);
    }
}
