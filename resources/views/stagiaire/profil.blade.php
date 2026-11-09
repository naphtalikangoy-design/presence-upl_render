@extends('layouts.app')
@section('title', 'Mon profil — UPL PRESENCE')
@section('content')
<div class="row g-3"><div class="col-lg-5"><div class="card p-4"><h5>Identité</h5><div class="text-muted small mb-2">Ces informations ne sont modifiables que par l'administration.</div>
  <dl class="row mb-0 small"><dt class="col-5">Nom complet</dt><dd class="col-7">{{ $st->nom_complet }}</dd><dt class="col-5">Matricule</dt><dd class="col-7">{{ $u->matricule }}</dd>
  <dt class="col-5">Sexe</dt><dd class="col-7">{{ $st->sexe === 'M' ? 'Masculin' : 'Féminin' }}</dd><dt class="col-5">Faculté</dt><dd class="col-7">{{ $st->faculte->nom }}</dd>
  <dt class="col-5">Filière</dt><dd class="col-7">{{ $st->filiere->nom }}</dd><dt class="col-5">Promotion</dt><dd class="col-7">{{ $st->promotion->nom }}</dd>
  <dt class="col-5">Accueil</dt><dd class="col-7">{{ $st->entreprise->nom ?? '—' }}</dd><dt class="col-5">Statut du compte</dt><dd class="col-7">{{ str_replace('_',' ',$u->statut) }}</dd></dl></div></div>
<div class="col-lg-7"><div class="card p-4"><h5>Mes coordonnées</h5>
  <form method="post" action="/stagiaire/profil" enctype="multipart/form-data" class="row g-3">@csrf @method('PUT')
    <div class="col-md-6"><label class="form-label">Téléphone</label><input name="telephone" value="{{ old('telephone',$st->telephone) }}" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">E-mail</label><input type="email" name="email" value="{{ old('email',$u->email) }}" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Photo (JPG/PNG/WebP, 2 Mo max)</label><input type="file" name="photo" accept="image/*" class="form-control"></div>
    <div class="col-12"><hr><h6>Changer le mot de passe (optionnel)</h6></div>
    <div class="col-md-4"><input type="password" name="password_actuel" class="form-control" placeholder="Mot de passe actuel"></div>
    <div class="col-md-4"><input type="password" name="password" class="form-control" placeholder="Nouveau"></div>
    <div class="col-md-4"><input type="password" name="password_confirmation" class="form-control" placeholder="Confirmation"></div>
    <div class="col-12"><button class="btn btn-upl">Enregistrer</button></div></form></div></div></div>
@endsection
