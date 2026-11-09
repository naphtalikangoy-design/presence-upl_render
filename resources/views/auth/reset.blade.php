@extends('layouts.app')
@section('title', 'Nouveau mot de passe')
@section('content')
<div class="row justify-content-center"><div class="col-md-5"><div class="card p-4"><h4 class="mb-3">Nouveau mot de passe</h4>
<form method="post" action="/reinitialiser-mot-de-passe">@csrf<input type="hidden" name="token" value="{{ $token }}">
<div class="mb-3"><label class="form-label">E-mail</label><input type="email" name="email" value="{{ old('email', $email) }}" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Nouveau mot de passe</label><input type="password" name="password" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Confirmation</label><input type="password" name="password_confirmation" class="form-control" required></div>
<button class="btn btn-upl w-100">Enregistrer</button></form></div></div></div>
@endsection
