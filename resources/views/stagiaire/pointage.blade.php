@extends('layouts.app')
@section('title', 'Pointage — UPL PRESENCE')
@section('content')
@if($demo)<div class="alert alert-warning"><strong>MODE DÉMO</strong> — les données réseau sont <u>simulées</u>. Aucune connexion Wi-Fi réelle n'est validée.</div>@endif
<div class="row g-3">
  <div class="col-lg-5"><div class="card p-3">
    <h5 class="mb-1">{{ $stagiaire->nom_complet }}</h5>
    <div class="text-muted small mb-3">{{ auth()->user()->matricule }} · <span id="clock"></span></div>
    <h6>Vérifications en temps réel</h6>
    <div id="checks">
      @foreach(['wifi_connecte'=>'Connexion Wi-Fi','reseau_officiel'=>'Réseau officiel','access_point'=>'Access Point autorisé','signal'=>'Signal Wi-Fi','batiment'=>'Bâtiment identifié','gps'=>'Position GPS vérifiée','autorisation'=>'Autorisation de pointage'] as $k=>$l)
      <div class="check"><span>{{ $l }}</span><span class="pill wait" data-k="{{ $k }}">…</span></div>
      @endforeach
    </div>
  </div></div>
  <div class="col-lg-7"><div class="card p-4 text-center">
    <div id="status" class="mb-3 fw-semibold">Vérification en cours…</div>
    <button id="btn" class="btn btn-upl btn-lg" disabled>EFFECTUER MON POINTAGE</button>
    <div id="msg" class="mt-3"></div>
  </div></div>
</div>
@endsection
@push('scripts')<script src="{{ asset('js/pointage.js') }}"></script>@endpush
