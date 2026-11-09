<?php
namespace App\Services;

use App\Models\{Presence, Stagiaire};
use Carbon\Carbon;

class ReportService
{
    public function __construct(private AttendanceService $att) {}

    /** @return array{titre:string,debut:Carbon,fin:Carbon,entetes:array,lignes:array,totaux:array,type:string} */
    public function build(string $type, Carbon $date, ?int $faculteId = null, ?int $batimentId = null): array
    {
        [$from, $to, $titre] = match ($type) {
            'semaine' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek(), 'Rapport hebdomadaire du ' . $date->copy()->startOfWeek()->format('d/m/Y') . ' au ' . $date->copy()->endOfWeek()->format('d/m/Y')],
            'mois' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth(), 'Rapport mensuel — ' . $date->translatedFormat('F Y')],
            default => [$date->copy()->startOfDay(), $date->copy()->startOfDay(), 'Rapport journalier du ' . $date->format('d/m/Y')],
        };
        $type = in_array($type, ['semaine', 'mois']) ? $type : 'jour';
        $stagiaires = Stagiaire::with('user', 'faculte', 'filiere')->whereHas('user', fn ($q) => $q->where('statut', 'actif'))
            ->when($faculteId, fn ($q) => $q->where('faculte_id', $faculteId))->get()->sortBy('nom');
        $pres = Presence::with('batiment')->whereBetween('date', [$from->toDateString(), $to->toDateString()])->whereIn('stagiaire_id', $stagiaires->pluck('id'))
            ->when($batimentId, fn ($q) => $q->where('batiment_id', $batimentId))->get()->groupBy('stagiaire_id');
        $ouv = $this->att->ouvrables(); $lignes = []; $t = ['attendus' => 0, 'presents' => 0, 'retards' => 0, 'absences' => 0];

        foreach ($stagiaires as $s) {
            $p = $pres->get($s->id, collect());
            if ($batimentId && $p->isEmpty()) continue;   // filtre bâtiment : uniquement les stagiaires présents dans ce bâtiment
            $arr = $p->where('type', 'arrivee');
            $att = $this->att->joursAttendus($s, $from, $to, $ouv); $pr = $arr->count(); $re = $arr->where('statut', 'retard')->count();
            $ab = $batimentId ? 0 : max(0, $att - $pr);
            $base = [$s->user->matricule, $s->nom_complet, $s->faculte->nom, $s->filiere->nom];
            if ($type === 'jour') {
                $a = $arr->first(); $d = $p->firstWhere('type', 'depart');
                $statut = $a ? ($a->statut === 'retard' ? "Retard ({$a->retard_minutes} min)" : 'Présent') : ($att ? 'Absent' : 'Non attendu');
                $lignes[] = array_merge($base, [$statut, $a?->pointe_a->format('H:i') ?? '', $d?->pointe_a->format('H:i') ?? '',
                    ($a && $d) ? intdiv((int) $a->pointe_a->diffInMinutes($d->pointe_a), 60) . 'h' . str_pad((int) $a->pointe_a->diffInMinutes($d->pointe_a) % 60, 2, '0', STR_PAD_LEFT) : '', $a?->batiment?->nom ?? '']);
            } else {
                $lignes[] = array_merge($base, [$att, $pr, $re, $ab, $att ? round(100 * $pr / $att) . ' %' : '—']);
            }
            $t['attendus'] += $att; $t['presents'] += $pr; $t['retards'] += $re; $t['absences'] += $ab;
        }
        $entetes = $type === 'jour' ? ['Matricule', 'Nom complet', 'Faculté', 'Filière', 'Statut', 'Arrivée', 'Départ', 'Durée', 'Bâtiment']
                                    : ['Matricule', 'Nom complet', 'Faculté', 'Filière', 'Jours attendus', 'Présences', 'Retards', 'Absences', 'Taux'];
        return ['titre' => $titre, 'debut' => $from, 'fin' => $to, 'entetes' => $entetes, 'lignes' => $lignes, 'totaux' => $t, 'type' => $type];
    }
}
