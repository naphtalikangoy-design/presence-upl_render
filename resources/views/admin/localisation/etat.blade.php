@extends('layouts.admin')
@section('title', 'État du réseau')
@section('content')
<div class="alert {{ $connecteur==='demo' ? 'alert-warning' : 'alert-info' }} small">Connecteur : <strong>{{ $connecteur }}</strong>{{ $connecteur==='demo' ? ' — données simulées, aucun état réel.' : '' }} · Un AP est « en ligne » s'il a été vu dans les {{ $ttl }} dernières minutes <em>d'après les événements reçus de l'infrastructure</em> (un AP sans client connecté peut apparaître hors ligne).</div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>AP</th><th>Bâtiment</th><th>Identifiant</th><th>Vu pour la dernière fois</th><th>Clients</th><th>Mesures (15 min)</th><th>Puissance</th><th>État</th></tr></thead><tbody>
@forelse($aps as $a)<tr><td>{{ $a->nom }}</td><td>{{ $a->batiment->nom }}</td><td class="font-monospace small">{{ $a->identifiant }}</td><td>{{ $a->derniere_vue_at?->diffForHumans() ?? 'jamais' }}</td><td>{{ $a->clients }}</td><td>{{ $a->mesures15 }}</td><td>{{ $a->puissance_dbm !== null ? $a->puissance_dbm.' dBm' : '—' }}</td>
<td>@if(!$a->actif)<span class="pill wait">désactivé</span>@elseif($a->en_ligne)<span class="pill ok">en ligne</span>@else<span class="pill ko">non vu</span>@endif</td></tr>
@empty<tr><td colspan="8" class="text-center text-muted py-3">Aucun Access Point déclaré.</td></tr>@endforelse</tbody></table></div></div>
@endsection
