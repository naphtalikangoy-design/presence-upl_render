@extends('layouts.admin')
@section('title', 'Configuration Wi-Fi')
@section('content')
<div class="row g-3"><div class="col-lg-6"><div class="card p-4"><h6>État de l'intégration</h6>
  <div class="check"><span>Mode réseau</span>@if($mode==='demo')<span class="pill wait">DÉMO — SIMULATION</span>@else<span class="pill ok">RÉEL</span>@endif</div>
  <div class="check"><span>Connecteur actif</span><strong>{{ $connecteur }}</strong></div>
  @foreach(['session_identity'=>'Identité de session','ap_identity'=>'Identité de l\'AP','terminal_mac'=>'MAC du terminal (hachée)','client_rssi'=>'RSSI client','tx_power'=>'Contrôle de puissance','ap_status'=>'État des AP'] as $k=>$l)
  <div class="check"><span>{{ $l }}</span><span class="pill {{ ($caps[$k] ?? false) ? 'ok' : 'wait' }}">{{ ($caps[$k] ?? false) ? 'supporté' : 'non supporté' }}</span></div>@endforeach
  <div class="check"><span>Secret webhook (.env)</span><span class="pill {{ $secretOk?'ok':'ko' }}">{{ $secretOk?'configuré':'absent' }}</span></div>
  <div class="check"><span>API contrôleur (.env)</span><span class="pill {{ $ctrlOk?'ok':'wait' }}">{{ $ctrlOk?'configurée':'non configurée' }}</span></div>
  <div class="check"><span>Dernière session reçue</span><span class="small">{{ $derniere ? $derniere->derniere_activite_at->diffForHumans() : 'aucune' }}</span></div>
  <div class="small text-muted mt-3">URL du webhook à fournir à l'administrateur réseau : <code>{{ $webhook }}</code><br>Le mode, le connecteur, les clés API et le secret se règlent <strong>uniquement dans .env</strong> (jamais via le navigateur).</div>
  <form method="post" action="/admin/configuration-reseau/tester" class="mt-3">@csrf<button class="btn btn-outline-secondary"><i class="bi bi-plug"></i> Tester l'intégration</button></form>
  @if(session('tests'))<div class="mt-3">@foreach(session('tests') as [$t,$m])<div class="small py-1"><span class="pill {{ $t==='ok'?'ok':($t==='ko'?'ko':'wait') }}">{{ $t==='ok'?'OK':($t==='ko'?'ÉCHEC':'INFO') }}</span> {{ $m }}</div>@endforeach</div>@endif
</div></div>
<div class="col-lg-6"><div class="card p-4"><h6>Paramètres Wi-Fi</h6><form method="post" action="/admin/configuration-reseau" class="row g-3">@csrf @method('PUT')
  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="wifi_required" value="1" id="wr" @checked(config('upl.wifi_required'))><label class="form-check-label" for="wr">Exiger la connexion au Wi-Fi officiel pour pointer</label></div>
    <div class="form-text">Désactivé : le pointage repose uniquement sur le GPS (assurance faible).</div></div>
  <div class="col-md-6"><label class="form-label">Nom du réseau autorisé (SSID)</label><input name="allowed_ssid" value="{{ old('allowed_ssid',config('upl.allowed_ssid')) }}" class="form-control" required></div>
  <div class="col-md-6"><label class="form-label">Validité d'une session (minutes)</label><input type="number" name="network_session_ttl_minutes" value="{{ old('network_session_ttl_minutes',config('upl.network_session_ttl_minutes')) }}" class="form-control" required></div>
  <div class="col-12"><label class="form-label">Politique de localisation</label><select name="localisation_policy" class="form-select"><option value="strict" @selected(config('upl.localisation_policy')==='strict')>Stricte — confirmation RSSI obligatoire (recommandé)</option><option value="ap_gps" @selected(config('upl.localisation_policy')==='ap_gps')>AP + GPS si aucun RSSI n'existe (assurance faible)</option></select></div>
  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="require_same_ip" value="1" id="ip" @checked(config('upl.require_same_ip'))><label class="form-check-label" for="ip">Exiger que l'IP du navigateur corresponde à celle de la session réseau</label></div><div class="form-text">À désactiver si l'application est derrière un NAT/proxy qui modifie l'IP.</div></div>
  <div class="col-12"><button class="btn btn-upl py-2">Enregistrer</button></div></form>
  <hr><div class="small">Les Access Points, leurs bâtiments et leurs capacités se gèrent dans <a href="/admin/points-acces">Access Points</a>.</div></div></div></div>
@endsection
