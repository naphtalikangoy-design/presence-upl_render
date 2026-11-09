<?php
namespace App\Services;

use App\Models\{JourOuvrable, Presence, Stagiaire};
use Carbon\Carbon;

class AttendanceService
{
    /** @return int[] jours ISO (1=lundi) ouvrables */
    public function ouvrables(): array { return JourOuvrable::where('actif', true)->pluck('jour_semaine')->all(); }

    /** Nombre de jours de présence attendus pour un stagiaire sur [from, to] (jours ouvrables, période de stage, jusqu'à aujourd'hui). */
    public function joursAttendus(Stagiaire $s, Carbon $from, Carbon $to, ?array $ouv = null): int
    {
        $ouv ??= $this->ouvrables();
        $debut = ($s->date_debut_stage ?? $s->created_at)->copy()->startOfDay();
        $a = $from->copy()->startOfDay(); if ($debut->gt($a)) $a = $debut;
        $b = $to->copy()->startOfDay(); if ($s->date_fin_stage && $s->date_fin_stage->lt($b)) $b = $s->date_fin_stage->copy()->startOfDay();
        if (now()->startOfDay()->lt($b)) $b = now()->startOfDay();
        $n = 0;
        for ($d = $a->copy(); $d->lte($b); $d->addDay()) if (in_array($d->dayOfWeekIso, $ouv)) $n++;
        return $n;
    }

    public function enStage(Stagiaire $s, Carbon $day): bool
    {
        return (!$s->date_debut_stage || $day->toDateString() >= $s->date_debut_stage->toDateString())
            && (!$s->date_fin_stage || $day->toDateString() <= $s->date_fin_stage->toDateString());
    }
}
