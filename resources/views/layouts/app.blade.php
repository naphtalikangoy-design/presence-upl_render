<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#0b1f3a"><link rel="manifest" href="/manifest.webmanifest"><link rel="apple-touch-icon" href="/icons/icon-192.png">
  <title>@yield('title', 'UPL PRESENCE')</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg upl-nav"><div class="container">
  <a class="navbar-brand d-flex align-items-center gap-2" href="/">@include('partials.logo')<span class="fw-semibold">UPL <span class="gold">PRESENCE</span></span></a>
  <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#nv"><i class="bi bi-list text-white fs-3"></i></button>
  <div id="nv" class="collapse navbar-collapse"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
    @auth
      @if(auth()->user()->role === 'stagiaire')
        @php $nn = auth()->user()->notifications()->whereNull('lu_at')->count(); @endphp
        <li class="nav-item"><a class="nav-link" href="/stagiaire/dashboard"><i class="bi bi-speedometer2"></i> Tableau de bord</a></li>
        <li class="nav-item"><a class="nav-link" href="/stagiaire/pointage"><i class="bi bi-geo-alt"></i> Pointage</a></li>
        <li class="nav-item"><a class="nav-link" href="/stagiaire/historique"><i class="bi bi-calendar3"></i> Historique</a></li>
        <li class="nav-item"><a class="nav-link" href="/stagiaire/notifications"><i class="bi bi-bell"></i>@if($nn)<span class="badge bg-warning text-dark">{{ $nn }}</span>@endif</a></li>
        <li class="nav-item"><a class="nav-link" href="/stagiaire/profil"><i class="bi bi-person"></i> Profil</a></li>
      @else
        <li class="nav-item"><a class="nav-link" href="/admin/dashboard">Administration</a></li>
      @endif
      <li class="nav-item"><form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-light">Déconnexion</button></form></li>
    @else
      <li class="nav-item"><a class="nav-link" href="/connexion">Se connecter</a></li>
      <li class="nav-item"><a class="btn btn-sm btn-gold" href="/inscription">Créer un compte</a></li>
    @endauth
  </ul></div>
</div></nav>
<main class="container py-4">@include('partials.flash')@yield('content')</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
<script>if('serviceWorker' in navigator){addEventListener('load',()=>navigator.serviceWorker.register('/sw.js').catch(()=>{}));}</script>
</body></html>
