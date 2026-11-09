@extends('layouts.admin')
@section('title', 'Paramètres')
@section('content')
<div class="row g-3">
<div class="col-lg-6"><div class="card p-4 h-100"><h6>Jours ouvrables</h6><form method="post" action="/admin/parametres/jours">@csrf @method('PUT')
  <div class="d-flex flex-wrap gap-3 mb-3">@foreach([1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi',7=>'Dimanche'] as $n=>$l)<div class="form-check"><input class="form-check-input" type="checkbox" name="jours[]" value="{{ $n }}" id="j{{ $n }}" @checked($jours[$n] ?? false)><label class="form-check-label" for="j{{ $n }}">{{ $l }}</label></div>@endforeach</div>
  <button class="btn btn-upl py-2">Enregistrer</button></form>
  <div class="small text-muted mt-3">Heures d'arrivée, de départ et de retard : <a href="/admin/horaires">Horaires</a>. Périodes de stage : fiche de chaque <a href="/admin/stagiaires">stagiaire</a>.</div></div></div>
<div class="col-lg-6"><div class="card p-4"><h6>Règles de pointage</h6><form method="post" action="/admin/parametres/regles" class="row g-2">@csrf @method('PUT')
  @foreach([['gps_max_accuracy_m','Précision GPS max (m)',config('upl.gps.max_accuracy_m')],['rssi_min_rssi_dbm','Signal minimal (dBm)',config('upl.rssi.min_rssi_dbm')],['rssi_min_confidence','Confiance min. multi-AP (0.5–0.99)',config('upl.rssi.min_confidence')],['rssi_min_confidence_single','Confiance min. 1 AP',config('upl.rssi.min_confidence_single')],['rssi_min_fingerprints_per_class','Empreintes min. par classe',config('upl.rssi.min_fingerprints_per_class')],['max_refused_attempts','Tentatives refusées max / 10 min',config('upl.anti_fraude.max_refused_attempts')]] as [$k,$l,$v])
  <div class="col-md-6"><label class="form-label small">{{ $l }}</label><input name="{{ $k }}" value="{{ old($k,$v) }}" class="form-control form-control-sm" required></div>@endforeach
  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="gps_optional_if_fingerprint" value="1" id="go" @checked(config('upl.gps.optional_if_fingerprint'))><label class="form-check-label small" for="go">Tolérer un GPS absent/imprécis si l'empreinte multi-AP confirme l'intérieur</label></div></div>
  <div class="col-12"><button class="btn btn-upl py-2">Enregistrer</button></div></form></div></div>
<div class="col-lg-6"><div class="card p-4"><h6>Liste officielle des matricules ({{ $nbOfficiels }})</h6><div class="small text-muted mb-2">Un étudiant dont le matricule figure dans la liste est activé automatiquement à l'inscription ; les autres attendent votre validation. Format : une ligne par étudiant, <code>matricule</code> ou <code>matricule;nom complet</code>.</div>
  <form method="post" action="/admin/parametres/matricules">@csrf<textarea name="liste" rows="5" class="form-control font-monospace mb-2" required></textarea><button class="btn btn-upl py-2">Importer</button></form>
  @if($nbOfficiels)<form method="post" action="/admin/parametres/matricules" class="mt-2" onsubmit="return confirm('Vider la liste officielle ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Vider la liste</button></form>@endif</div></div>
<div class="col-lg-6"><div class="card p-4"><h6>Logo de l'UPL</h6><div class="mb-2">@include('partials.logo') <span class="small text-muted">Emplacement configurable (PNG, 1 Mo max).</span></div>
  <form method="post" action="/admin/parametres/logo" enctype="multipart/form-data">@csrf<input type="file" name="logo" accept="image/png" class="form-control mb-2" required><button class="btn btn-upl py-2">Téléverser</button></form></div></div>
</div>
@endsection
