@extends('layouts.admin')
@section('title', $st ? 'Modifier le stagiaire' : 'Nouveau stagiaire')
@section('content')
@php $v = fn($k,$d=null)=>old($k,$d); @endphp
<div class="card p-4"><form method="post" action="{{ $st ? "/admin/stagiaires/{$st->id}" : '/admin/stagiaires' }}" class="row g-3">@csrf @if($st)@method('PUT')@endif
  @foreach([['nom','Nom',$st?->nom],['postnom','Postnom',$st?->postnom],['prenom','Prénom',$st?->prenom],['matricule','Matricule',$st?->user->matricule],['telephone','Téléphone',$st?->telephone],['email','E-mail',$st?->user->email,'email']] as $f)
  <div class="col-md-4"><label class="form-label">{{ $f[1] }}</label><input type="{{ $f[3] ?? 'text' }}" name="{{ $f[0] }}" value="{{ $v($f[0],$f[2]) }}" class="form-control" required></div>@endforeach
  <div class="col-md-4"><label class="form-label">Sexe</label><select name="sexe" class="form-select" required>@foreach(['M'=>'Masculin','F'=>'Féminin'] as $k=>$l)<option value="{{ $k }}" @selected($v('sexe',$st?->sexe)===$k)>{{ $l }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label">Faculté</label><select name="faculte_id" class="form-select" required>@foreach($facultes as $f)<option value="{{ $f->id }}" @selected($v('faculte_id',$st?->faculte_id)==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label">Filière</label><select name="filiere_id" class="form-select" required>@foreach($filieres as $f)<option value="{{ $f->id }}" @selected($v('filiere_id',$st?->filiere_id)==$f->id)>{{ $facultes->firstWhere('id',$f->faculte_id)?->nom }} — {{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label">Promotion</label><select name="promotion_id" class="form-select" required>@foreach($promotions as $f)<option value="{{ $f->id }}" @selected($v('promotion_id',$st?->promotion_id)==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
  <div class="col-md-8"><label class="form-label">Entreprise / institution d'accueil</label><input name="entreprise" list="ents" value="{{ $v('entreprise',$st?->entreprise?->nom) }}" class="form-control"><datalist id="ents">@foreach($entreprises as $e)<option value="{{ $e->nom }}">@endforeach</datalist></div>
  <div class="col-md-4"></div>
  <div class="col-md-4"><label class="form-label">Début du stage</label><input type="date" name="date_debut_stage" value="{{ $v('date_debut_stage',$st?->date_debut_stage?->toDateString()) }}" class="form-control"></div>
  <div class="col-md-4"><label class="form-label">Fin du stage</label><input type="date" name="date_fin_stage" value="{{ $v('date_fin_stage',$st?->date_fin_stage?->toDateString()) }}" class="form-control"></div>
  <div class="col-md-4"><label class="form-label">Mot de passe {{ $st ? '(laisser vide pour conserver)' : '' }}</label><input type="password" name="password" class="form-control" @if(!$st) required @endif></div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-upl py-2">Enregistrer</button><a class="btn btn-outline-secondary" href="/admin/stagiaires">Annuler</a></div></form></div>
@endsection
