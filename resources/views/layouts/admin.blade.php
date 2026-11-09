<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#0b1f3a"><link rel="manifest" href="/manifest.webmanifest">
  <title>@yield('title', 'Administration') — UPL PRESENCE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
  @stack('head')
</head>
<body>
@php
  $menu = [
    'Général' => [['/admin/dashboard','speedometer2','Tableau de bord'],['/admin/stagiaires','people','Stagiaires'],['/admin/presences','check2-square','Présences'],['/admin/rapports','file-earmark-bar-graph','Rapports']],
    'Référentiels' => [['/admin/facultes','bank','Facultés'],['/admin/filieres','diagram-3','Filières'],['/admin/promotions','mortarboard','Promotions'],['/admin/entreprises','briefcase','Entreprises d\'accueil']],
    'Localisation intérieure' => [['/admin/batiments','building','Bâtiments'],['/admin/points-acces','wifi','Access Points'],['/admin/configuration-reseau','sliders','Configuration Wi-Fi'],['/admin/puissance','lightning-charge','Puissance d\'émission'],['/admin/mesures-rssi','reception-4','Mesures RSSI'],['/admin/calibration','rulers','Calibration Wi-Fi'],['/admin/zones','geo-alt','Zones autorisées'],['/admin/connexions','hdd-network','Historique des connexions'],['/admin/tests-localisation','bullseye','Tests de localisation'],['/admin/etat-reseau','activity','État du réseau']],
    'Système' => [['/admin/horaires','clock','Horaires'],['/admin/journaux','journal-text','Journaux'],['/admin/parametres','gear','Paramètres']],
  ];
@endphp
<div class="d-flex admin-shell">
  <aside class="admin-side collapse d-lg-block" id="side">
    <div class="p-3 d-flex align-items-center gap-2 border-bottom border-secondary">@include('partials.logo')<strong class="text-white">UPL <span class="gold">PRESENCE</span></strong></div>
    @foreach($menu as $group => $items)
      <div class="side-group">{{ $group }}</div>
      @foreach($items as [$url,$icon,$label])
        <a href="{{ $url }}" class="side-link {{ request()->is(ltrim($url,'/').'*') ? 'active' : '' }}"><i class="bi bi-{{ $icon }}"></i> {{ $label }}</a>
      @endforeach
    @endforeach
  </aside>
  <div class="flex-grow-1 min-w-0">
    <header class="admin-top d-flex justify-content-between align-items-center px-3">
      <button class="btn d-lg-none" data-bs-toggle="collapse" data-bs-target="#side"><i class="bi bi-list fs-4"></i></button>
      <h5 class="mb-0">@yield('title')</h5>
      <div class="d-flex align-items-center gap-3">
        @if(config('upl.network_mode') === 'demo')<span class="badge bg-warning text-dark">MODE DÉMO</span>@endif
        <span class="text-muted small d-none d-md-inline">{{ auth()->user()->name }}</span>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Déconnexion</button></form>
      </div>
    </header>
    <main class="p-3 p-lg-4">@include('partials.flash')@yield('content')</main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body></html>
