@extends('layouts.admin')
@section('title', 'Calibration Wi-Fi')
@section('content')
<div class="alert alert-info small"><strong>Procédure :</strong> avec un téléphone connecté au SSID officiel, relevez au moins <strong>{{ $min }} points à l'intérieur</strong> (chaque zone autorisée) et <strong>{{ $min }} points à l'extérieur</strong> (entrée, parking, couloirs voisins, étages voisins). Laissez ≥ 3 échantillons s'accumuler à chaque point. Recalibrez après tout changement d'AP, de puissance ou de canal. Sans calibration suffisante, le pointage est refusé (LOCALISATION NON CONFIRMÉE).</div>
<div class="row g-3 mb-3">@foreach($batiments as $b)@php $c=$compte[$b->id] ?? collect(); $i=$c['interieur'] ?? 0; $e=$c['exterieur'] ?? 0; @endphp
<div class="col-md-4"><div class="card stat"><strong>{{ $b->nom }}</strong><div class="small mt-1">Intérieur : <span class="pill {{ $i>=$min?'ok':'ko' }}">{{ $i }}/{{ $min }}</span> Extérieur : <span class="pill {{ $e>=$min?'ok':'ko' }}">{{ $e }}/{{ $min }}</span></div></div></div>@endforeach</div>
<div class="card p-4 mb-3"><h6>Ajouter une empreinte</h6><form method="post" action="/admin/calibration" class="row g-3">@csrf
  <div class="col-md-3"><label class="form-label">Bâtiment</label><select name="batiment_id" class="form-select" required>@foreach($batiments as $b)<option value="{{ $b->id }}">{{ $b->nom }}</option>@endforeach</select></div>
  <div class="col-md-2"><label class="form-label">Classe</label><select name="classe" class="form-select"><option value="interieur">Intérieur</option><option value="exterieur">Extérieur</option></select></div>
  <div class="col-md-3"><label class="form-label">Zone intérieure</label><select name="zone_interieure_id" class="form-select"><option value="">—</option>@foreach($zones as $z)<option value="{{ $z->id }}">{{ $z->batiment->nom ?? '' }} — {{ $z->nom }}{{ $z->autorise?'':' (non autorisée)' }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label">Appareil de relevé</label><input name="appareil" class="form-control" placeholder="Ex. Samsung A14"></div>
  <div class="col-md-5"><label class="form-label">Capture réelle : identifiant (matricule) du téléphone connecté</label><input name="username" class="form-control" placeholder="Session Wi-Fi active requise"></div>
  <div class="col-md-7"><label class="form-label">…ou signature manuelle (JSON {"bssid": rssi})</label><textarea name="signature" rows="2" class="form-control font-monospace" placeholder='{"aabbccddeeff": -52, "112233445566": -67}'></textarea></div>
  <div class="col-12"><button class="btn btn-upl py-2">Enregistrer l'empreinte</button></div></form></div>
<div class="card p-3"><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Date</th><th>Bâtiment</th><th>Classe</th><th>Zone</th><th>Signature (AP : dBm)</th><th>Éch.</th><th>État</th><th></th></tr></thead><tbody>
@forelse($fps as $f)<tr><td>{{ $f->releve_at?->format('d/m/Y H:i') }}</td><td>{{ $f->batiment->nom }}</td><td>{{ $f->classe }}@if($f->classeEffective()!==$f->classe) <span class="pill ko">zone non autorisée</span>@endif</td><td>{{ $f->zone->nom ?? '—' }}</td>
<td class="small font-monospace">@foreach($f->signature as $k=>$v){{ substr($k,-4) }}:{{ $v }} @endforeach</td><td>{{ $f->nb_echantillons }}</td><td>@if($f->est_demo)<span class="pill wait">démo</span> @endif<span class="pill {{ $f->actif?'ok':'wait' }}">{{ $f->actif?'actif':'inactif' }}</span></td>
<td class="text-end text-nowrap"><form class="d-inline" method="post" action="/admin/calibration/{{ $f->id }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">{{ $f->actif?'Désactiver':'Activer' }}</button></form>
<form class="d-inline" method="post" action="/admin/calibration/{{ $f->id }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
@empty<tr><td colspan="8" class="text-center text-muted py-3">Aucune empreinte.</td></tr>@endforelse</tbody></table></div></div>
@endsection
