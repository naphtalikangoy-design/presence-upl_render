@extends('layouts.admin')
@section('title', 'Présences')
@section('content')
<form class="card p-3 mb-3 row g-2 flex-row" method="get">
  <div class="col-md-2"><input type="date" name="date_debut" value="{{ request('date_debut') }}" class="form-control" title="Du"></div>
  <div class="col-md-2"><input type="date" name="date_fin" value="{{ request('date_fin') }}" class="form-control" title="Au"></div>
  <div class="col-md-2"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Matricule / nom"></div>
  <div class="col-md-2"><select name="faculte_id" class="form-select"><option value="">Faculté</option>@foreach($facultes as $f)<option value="{{ $f->id }}" @selected(request('faculte_id')==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-2"><select name="batiment_id" class="form-select"><option value="">Bâtiment</option>@foreach($batiments as $f)<option value="{{ $f->id }}" @selected(request('batiment_id')==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-1"><select name="statut" class="form-select"><option value="">Statut</option>@foreach(['present','retard','absent'] as $s)<option @selected(request('statut')===$s)>{{ $s }}</option>@endforeach</select></div>
  <div class="col-md-1 d-grid"><button class="btn btn-upl py-2"><i class="bi bi-search"></i></button></div></form>
<div class="d-flex justify-content-end mb-2"><a href="/admin/presences/create" class="btn btn-gold"><i class="bi bi-plus-lg"></i> Saisie manuelle</a></div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle table-sm"><thead><tr><th>Date</th><th>Heure</th><th>Type</th><th>Stagiaire</th><th>Faculté</th><th>Bâtiment</th><th>Statut</th><th>Wi-Fi</th><th></th></tr></thead><tbody>
@forelse($rows as $p)<tr><td>{{ $p->date->format('d/m/Y') }}</td><td>{{ $p->pointe_a->format('H:i') }}</td><td>{{ $p->type }}</td><td>{{ $p->stagiaire->user->matricule }}<div class="small text-muted">{{ $p->stagiaire->nom_complet }}</div></td><td>{{ $p->stagiaire->faculte->nom }}</td><td>{{ $p->batiment->nom ?? '—' }}</td>
  <td><span class="pill {{ $p->statut==='present'?'ok':($p->statut==='retard'?'wait':'ko') }}">{{ $p->statut }}</span>{{ $p->corrige ? ' ✎' : '' }}</td>
  <td class="small">{{ $p->wifi_source }}@if($p->wifi_source==='demo') <span class="pill wait">simulé</span>@endif</td><td><a class="btn btn-sm btn-outline-secondary" href="/admin/presences/{{ $p->id }}/edit">Détail</a></td></tr>
@empty<tr><td colspan="9" class="text-center text-muted py-4">Aucune présence.</td></tr>@endforelse</tbody></table></div>{{ $rows->links() }}</div>
@endsection
