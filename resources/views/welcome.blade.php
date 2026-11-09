@extends('layouts.app')
@section('title', 'UPL PRESENCE — Pointage des stagiaires')
@section('content')
<section class="hero mb-5"><div class="row align-items-center"><div class="col-lg-8 position-relative">
  <div class="mb-3">@include('partials.logo')</div>
  <h1 class="display-5">UPL <span class="gold">PRESENCE</span></h1>
  <p class="lead mt-3">La plateforme de pointage des stagiaires de l'Université Protestante de Lubumbashi : une présence validée par l'identité, le réseau Wi-Fi officiel et la localisation.</p>
  <div class="d-flex flex-wrap gap-2 mt-4"><a href="/connexion" class="btn btn-gold btn-lg px-4">Se connecter</a><a href="/inscription" class="btn btn-outline-light btn-lg px-4">Créer un compte</a></div>
</div></div></section>

<section class="mb-5"><h3 class="mb-4 text-center">Fonctionnalités</h3><div class="row g-3">
  @foreach([['shield-lock','Identité vérifiée','Connexion par matricule, validation administrative et liste officielle des étudiants.'],
            ['wifi','Wi-Fi officiel','La session réseau est confirmée côté serveur par l\'infrastructure de l\'UPL.'],
            ['geo-alt','Localisation intérieure','Empreintes Wi-Fi (RSSI) et GPS pour confirmer la présence dans un bâtiment autorisé.'],
            ['calendar-check','Historique clair','Calendrier visuel, retards, durée de présence, notifications.'],
            ['bar-chart-line','Rapports','Statistiques par faculté et bâtiment, exports PDF et Excel.'],
            ['phone','Application mobile','Interface responsive installable sur smartphone (PWA).']] as [$i,$t,$d])
    <div class="col-md-6 col-lg-4"><div class="card feature"><i class="bi bi-{{ $i }}"></i><h5 class="mt-2">{{ $t }}</h5><p class="text-muted mb-0">{{ $d }}</p></div></div>
  @endforeach
</div></section>

<section class="mb-4"><h3 class="mb-4 text-center">Comment fonctionne le pointage ?</h3><div class="card p-4"><div class="row g-4">
  @foreach(['Connectez-vous avec votre matricule (créez un compte si nécessaire).','Connectez votre téléphone au Wi-Fi officiel de l\'UPL et entrez dans le bâtiment.','Ouvrez « Pointage » et autorisez la localisation de votre navigateur.','Le serveur vérifie votre session Wi-Fi, le point d\'accès, le signal et votre position.','Si tout est conforme, votre présence est enregistrée et vous recevez une confirmation.'] as $i => $s)
    <div class="col-md-6 d-flex gap-3"><div class="step-n">{{ $i + 1 }}</div><div class="pt-2">{{ $s }}</div></div>
  @endforeach
</div></div></section>

<footer class="site-footer"><div class="row"><div class="col-md-8"><strong class="text-white">Université Protestante de Lubumbashi</strong><br>Lubumbashi, Haut-Katanga — République démocratique du Congo<br><small>UPL PRESENCE · plateforme de gestion des présences des stagiaires</small></div>
<div class="col-md-4 text-md-end mt-3 mt-md-0"><a class="text-white" href="/connexion">Connexion</a> · <a class="text-white" href="/inscription">Inscription</a></div></div></footer>
@endsection
