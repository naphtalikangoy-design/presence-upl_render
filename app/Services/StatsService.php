<?php
namespace App\Services;

use App\Models\{Presence, Stagiaire, User};
use Illuminate\Support\Facades\DB;

class StatsService
{
    public function __construct(private AttendanceService $att) {}

    public function dashboard(): array
    {
        $today = now()->startOfDay(); $ouv = $this->att->ouvrables();
        $ouvrable = in_array(now()->dayOfWeekIso, $ouv);
        $actifs = Stagiaire::whereHas('user', fn ($q) => $q->where('statut', 'actif'))->get();
        $attendus = $actifs->filter(fn ($s) => $this->att->enStage($s, $today));
        $arr = Presence::whereDate('date', $today->toDateString())->where('type', 'arrivee')->get();
        $presents = $arr->whereIn('stagiaire_id', $attendus->pluck('id'))->count();

        $since = now()->subDays(29)->toDateString();
        $group = fn ($table, $fk, $col) => Presence::query()->where('presences.type', 'arrivee')->where('presences.date', '>=', $since)
            ->join('stagiaires', 'presences.stagiaire_id', '=', 'stagiaires.id')
            ->join($table, "stagiaires.$fk", '=', "$table.id")->groupBy("$table.$col")->selectRaw("$table.$col as label, count(*) as total")->pluck('total', 'label')->all();
        $parFaculte = $group('facultes', 'faculte_id', 'nom');
        $parBatiment = Presence::query()->where('presences.type', 'arrivee')->where('presences.date', '>=', $since)
            ->join('batiments', 'presences.batiment_id', '=', 'batiments.id')->groupBy('batiments.nom')->selectRaw('batiments.nom as label, count(*) as total')->pluck('total', 'label')->all();

        $semaines = []; $mois = [];
        for ($i = 7; $i >= 0; $i--) {
            $a = now()->startOfWeek()->subWeeks($i); $b = $a->copy()->endOfWeek();
            $q = Presence::where('type', 'arrivee')->whereBetween('date', [$a->toDateString(), $b->toDateString()]);
            $semaines[] = ['label' => $a->format('d/m'), 'presents' => (clone $q)->count(), 'retards' => (clone $q)->where('statut', 'retard')->count()];
        }
        for ($i = 5; $i >= 0; $i--) {
            $a = now()->startOfMonth()->subMonths($i); $b = $a->copy()->endOfMonth();
            $q = Presence::where('type', 'arrivee')->whereBetween('date', [$a->toDateString(), $b->toDateString()]);
            $mois[] = ['label' => $a->translatedFormat('M Y'), 'presents' => (clone $q)->count(), 'retards' => (clone $q)->where('statut', 'retard')->count()];
        }
        return [
            'total' => Stagiaire::count(), 'presents' => $arr->count(), 'retards' => $arr->where('statut', 'retard')->count(),
            'absences' => $ouvrable ? max(0, $attendus->count() - $presents) : 0, 'ouvrable' => $ouvrable,
            'attente' => User::where('role', 'stagiaire')->where('statut', 'en_attente')->count(),
            'parFaculte' => $parFaculte, 'parBatiment' => $parBatiment, 'semaines' => $semaines, 'mois' => $mois,
        ];
    }
}
