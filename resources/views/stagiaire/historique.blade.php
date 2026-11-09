@extends('layouts.app')
@section('title', 'Historique — UPL PRESENCE')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Mon historique</h4>
  <div class="btn-group"><a class="btn btn-outline-secondary" href="?mois={{ $mois->copy()->subMonth()->format('Y-m') }}"><i class="bi bi-chevron-left"></i></a>
  <span class="btn btn-light disabled text-dark">{{ $mois->translatedFormat('F Y') }}</span>
  <a class="btn btn-outline-secondary" href="?mois={{ $mois->copy()->addMonth()->format('Y-m') }}"><i class="bi bi-chevron-right"></i></a></div></div>
<div class="row g-3"><div class="col-lg-5"><div class="card p-3">
  <div class="cal mb-2">@foreach(['L','M','M','J','V','S','D'] as $h)<div class="h">{{ $h }}</div>@endforeach
    @for($i=0;$i<$offset;$i++)<div class="d blank"></div>@endfor
    @foreach($cal as $c)<div class="d {{ $c['cls'] }} {{ $c['today'] ? 'today' : '' }}">{{ $c['n'] }}</div>@endforeach</div>
  <div class="small d-flex flex-wrap gap-3 mt-2"><span><span class="pill ok">&nbsp;</span> Présent</span><span><span class="pill ko">&nbsp;</span> Absent</span><span style="color:#f08c1d">■ Retard</span><span class="text-secondary">■ Non ouvrable</span></div>
</div></div>
<div class="col-lg-7"><div class="card p-3"><div class="table-responsive"><table class="table align-middle mb-0">
  <thead><tr><th>Date</th><th>Arrivée</th><th>Départ</th><th>Statut</th><th>Bâtiment</th><th>Durée</th></tr></thead><tbody>
  @forelse($jours as $j)<tr><td>{{ $j['date']->format('d/m/Y') }}</td><td>{{ $j['arrivee']?->format('H:i') ?? '—' }}</td><td>{{ $j['depart']?->format('H:i') ?? '—' }}</td>
    <td>@if($j['statut']==='retard')<span class="pill wait">Retard {{ $j['retard'] }} min</span>@else<span class="pill ok">Présent</span>@endif</td><td>{{ $j['batiment'] ?? '—' }}</td>
    <td>{{ $j['duree'] !== null ? intdiv($j['duree'],60).'h'.str_pad($j['duree']%60,2,'0',STR_PAD_LEFT) : '—' }}</td></tr>
  @empty<tr><td colspan="6" class="text-center text-muted py-4">Aucune présence ce mois-ci.</td></tr>@endforelse</tbody></table></div></div></div></div>
@endsection
