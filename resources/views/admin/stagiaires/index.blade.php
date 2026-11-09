@extends('layouts.admin')
@section('title', 'Stagiaires')
@section('content')
<form class="card p-3 mb-3 row g-2 flex-row" method="get">
  <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Matricule ou nom"></div>
  <div class="col-md-2"><select name="faculte_id" class="form-select"><option value="">Faculté</option>@foreach($facultes as $f)<option value="{{ $f->id }}" @selected(request('faculte_id')==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-2"><select name="filiere_id" class="form-select"><option value="">Filière</option>@foreach($filieres as $f)<option value="{{ $f->id }}" @selected(request('filiere_id')==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-2"><select name="promotion_id" class="form-select"><option value="">Promotion</option>@foreach($promotions as $f)<option value="{{ $f->id }}" @selected(request('promotion_id')==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-2"><select name="statut" class="form-select"><option value="">Statut</option>@foreach(['en_attente','actif','rejete','desactive'] as $s)<option value="{{ $s }}" @selected(request('statut')===$s)>{{ str_replace('_',' ',$s) }}</option>@endforeach</select></div>
  <div class="col-md-1 d-grid"><button class="btn btn-upl py-2"><i class="bi bi-search"></i></button></div></form>
<div class="d-flex justify-content-end mb-2"><a href="/admin/stagiaires/create" class="btn btn-gold"><i class="bi bi-plus-lg"></i> Ajouter un stagiaire</a></div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Matricule</th><th>Nom complet</th><th>Faculté / Filière</th><th>Promotion</th><th>Statut</th><th></th></tr></thead><tbody>
@forelse($rows as $s)<tr><td>{{ $s->user->matricule }}</td><td><a href="/admin/stagiaires/{{ $s->id }}">{{ $s->nom_complet }}</a></td><td>{{ $s->faculte->nom }}<div class="small text-muted">{{ $s->filiere->nom }}</div></td><td>{{ $s->promotion->nom }}</td>
  <td><span class="pill {{ ['actif'=>'ok','en_attente'=>'wait','rejete'=>'ko','desactive'=>'ko'][$s->user->statut] }}">{{ str_replace('_',' ',$s->user->statut) }}</span></td>
  <td class="text-end text-nowrap">
    @if($s->user->statut==='en_attente')<form class="d-inline" method="post" action="/admin/stagiaires/{{ $s->id }}/valider">@csrf<button class="btn btn-sm btn-success">Valider</button></form>
      <form class="d-inline" method="post" action="/admin/stagiaires/{{ $s->id }}/rejeter">@csrf<button class="btn btn-sm btn-outline-danger">Rejeter</button></form>@endif
    <a class="btn btn-sm btn-outline-secondary" href="/admin/stagiaires/{{ $s->id }}/edit"><i class="bi bi-pencil"></i></a></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted py-4">Aucun stagiaire.</td></tr>@endforelse</tbody></table></div>{{ $rows->links() }}</div>
@endsection
