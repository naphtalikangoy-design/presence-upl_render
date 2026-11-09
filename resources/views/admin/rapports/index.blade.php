@extends('layouts.admin')
@section('title', 'Rapports')
@section('content')
@php $q = http_build_query(request()->only('type','date','faculte_id','batiment_id')); @endphp
<form class="card p-3 mb-3 row g-2 flex-row" method="get">
  <div class="col-md-2"><select name="type" class="form-select">@foreach(['jour'=>'Journalier','semaine'=>'Hebdomadaire','mois'=>'Mensuel'] as $k=>$l)<option value="{{ $k }}" @selected($rap['type']===$k)>{{ $l }}</option>@endforeach</select></div>
  <div class="col-md-2"><input type="date" name="date" value="{{ request('date', now()->toDateString()) }}" class="form-control"></div>
  <div class="col-md-3"><select name="faculte_id" class="form-select"><option value="">Toutes les facultés</option>@foreach($facultes as $f)<option value="{{ $f->id }}" @selected(request('faculte_id')==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-3"><select name="batiment_id" class="form-select"><option value="">Tous les bâtiments</option>@foreach($batiments as $f)<option value="{{ $f->id }}" @selected(request('batiment_id')==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-2 d-grid"><button class="btn btn-upl py-2">Générer</button></div></form>
<div class="card p-3"><div class="d-flex justify-content-between flex-wrap gap-2 mb-2"><h5 class="mb-0">{{ $rap['titre'] }}</h5>
  <div class="btn-group"><a class="btn btn-outline-secondary" href="/admin/rapports/pdf?{{ $q }}"><i class="bi bi-file-earmark-pdf"></i> PDF</a><a class="btn btn-outline-secondary" href="/admin/rapports/excel?{{ $q }}"><i class="bi bi-file-earmark-excel"></i> Excel</a><a class="btn btn-outline-secondary" target="_blank" href="/admin/rapports/imprimer?{{ $q }}"><i class="bi bi-printer"></i> Imprimer</a></div></div>
  @if($rap['type']!=='jour')<div class="small mb-2">Attendus : <strong>{{ $rap['totaux']['attendus'] }}</strong> · Présences : <strong>{{ $rap['totaux']['presents'] }}</strong> · Retards : <strong>{{ $rap['totaux']['retards'] }}</strong> · Absences : <strong>{{ $rap['totaux']['absences'] }}</strong></div>@endif
  <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr>@foreach($rap['entetes'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead><tbody>
  @forelse($rap['lignes'] as $l)<tr>@foreach($l as $c)<td>{{ $c }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($rap['entetes']) }}" class="text-center text-muted py-3">Aucune donnée.</td></tr>@endforelse</tbody></table></div></div>
@endsection
