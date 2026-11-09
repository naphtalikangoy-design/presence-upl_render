@extends('layouts.app')
@section('title', 'Connexion — UPL PRESENCE')
@section('content')
<div class="row justify-content-center"><div class="col-md-5"><div class="card p-4">
  <h4 class="mb-3">Connexion</h4>
  <form method="post" action="/connexion">@csrf
    <div class="mb-3"><label class="form-label">Matricule</label><input name="matricule" value="{{ old('matricule') }}" class="form-control" required autofocus></div>
    <div class="mb-3"><label class="form-label">Mot de passe</label>
      <div class="input-group"><input id="pw" type="password" name="password" class="form-control" required>
      <button type="button" class="btn btn-outline-secondary" onclick="pw.type=pw.type==='password'?'text':'password'"><i class="bi bi-eye"></i></button></div></div>
    <button class="btn btn-upl w-100">Se connecter</button>
    <div class="d-flex justify-content-between mt-3 small"><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a><a href="/inscription">Créer un compte</a></div>
  </form></div></div></div>
@endsection
