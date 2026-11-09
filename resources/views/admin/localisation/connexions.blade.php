@extends('layouts.admin')
@section('title', 'Historique des connexions')
@section('content')
<form class="card p-3 mb-3 row g-2 flex-row" method="get"><div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Identifiant / matricule"></div>
<div class="col-md-3 pt-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="actives" value="1" id="a" @checked(request('actives'))><label class="form-check-label" for="a">Sessions actives seulement</label></div></div><div class="col-md-2 d-grid"><button class="btn btn-upl py-2">Filtrer</button></div></form>
<div class="card p-3"><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Début</th><th>Dernière activité</th><th>Identifiant</th><th>AP</th><th>SSID</th><th>IP</th><th>Source</th><th>État</th></tr></thead><tbody>
@forelse($rows as $s)<tr><td>{{ $s->debut_at?->format('d/m H:i') }}</td><td>{{ $s->derniere_activite_at?->format('d/m H:i') }}</td><td>{{ $s->username }}</td><td>{{ $s->pointAcces->nom ?? ($s->ap_identifiant ? $s->ap_identifiant.' (non déclaré)' : '—') }}</td><td>{{ $s->ssid }}</td><td>{{ $s->ip_address }}</td>
<td>{{ $s->source }} @if($s->est_demo)<span class="pill wait">simulé</span>@endif</td><td><span class="pill {{ $s->fin_at ? 'wait' : 'ok' }}">{{ $s->fin_at ? 'terminée' : 'active' }}</span></td></tr>
@empty<tr><td colspan="8" class="text-center text-muted py-3">Aucune session.</td></tr>@endforelse</tbody></table></div>{{ $rows->links() }}</div>
@endsection
