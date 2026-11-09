@extends('layouts.app')
@section('title', 'Mot de passe oublié')
@section('content')
<div class="row justify-content-center"><div class="col-md-5"><div class="card p-4"><h4 class="mb-3">Mot de passe oublié</h4>
<form method="post" action="/mot-de-passe-oublie">@csrf<div class="mb-3"><label class="form-label">Adresse e-mail du compte</label><input type="email" name="email" class="form-control" required></div>
<button class="btn btn-upl w-100">Envoyer le lien</button><div class="mt-3 small"><a href="/connexion">Retour à la connexion</a></div></form></div></div></div>
@endsection
