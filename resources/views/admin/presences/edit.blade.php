@extends('layouts.admin')
@section('title', 'Présence — détail et correction')
@section('content')
<div class="row g-3"><div class="col-lg-5"><div class="card p-4"><h6>Détail</h6><dl class="row small mb-0">
  <dt class="col-5">Stagiaire</dt><dd class="col-7">{{ $p->stagiaire->nom_complet }} ({{ $p->stagiaire->user->matricule }})</dd>
  <dt class="col-5">Date / heure</dt><dd class="col-7">{{ $p->date->format('d/m/Y') }} {{ $p->pointe_a->format('H:i:s') }} ({{ $p->type }})</dd>
  <dt class="col-5">Bâtiment</dt><dd class="col-7">{{ $p->batiment->nom ?? '—' }}</dd><dt class="col-5">Access Point</dt><dd class="col-7">{{ $p->pointAcces->nom ?? '—' }}</dd>
  <dt class="col-5">Source Wi-Fi</dt><dd class="col-7">{{ $p->wifi_source }} {{ $p->wifi_verifie ? '(vérifié)' : '(non vérifié réellement)' }}</dd>
  <dt class="col-5">Coordonnées GPS</dt><dd class="col-7">@if($p->wifi_source==='manuel')—@else{{ $p->latitude }}, {{ $p->longitude }} <a target="_blank" rel="noopener" href="https://www.openstreetmap.org/?mlat={{ $p->latitude }}&mlon={{ $p->longitude }}#map=19/{{ $p->latitude }}/{{ $p->longitude }}">carte</a>@endif</dd>
  <dt class="col-5">Précision / distance</dt><dd class="col-7">{{ round($p->precision_gps) }} m / {{ round($p->distance_m) }} m</dd>
  @if($p->justification)<dt class="col-5">Justification</dt><dd class="col-7">{{ $p->justification }}</dd>@endif</dl></div>
  <div class="card p-3 mt-3"><h6>Historique des modifications</h6>@forelse($hist as $h)<div class="small border-top py-2"><strong>{{ $h->action }}</strong> — {{ $h->user->name ?? 'système' }} · {{ $h->created_at->format('d/m/Y H:i') }}<pre class="mb-0 small text-muted" style="white-space:pre-wrap">{{ json_encode($h->details, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></div>@empty<div class="small text-muted">Aucune modification.</div>@endforelse</div></div>
<div class="col-lg-7"><div class="card p-4"><h6>Corriger la présence</h6><form method="post" action="/admin/presences/{{ $p->id }}" class="row g-3">@csrf @method('PUT')
  <div class="col-md-6"><label class="form-label">Statut</label><select name="statut" class="form-select">@foreach(['present','retard','absent'] as $s)<option @selected(old('statut',$p->statut)===$s)>{{ $s }}</option>@endforeach</select></div>
  <div class="col-md-6"><label class="form-label">Minutes de retard</label><input type="number" name="retard_minutes" min="0" value="{{ old('retard_minutes',$p->retard_minutes) }}" class="form-control"></div>
  <div class="col-12"><label class="form-label">Date et heure</label><input type="datetime-local" name="pointe_a" value="{{ old('pointe_a',$p->pointe_a->format('Y-m-d\TH:i')) }}" class="form-control"></div>
  <div class="col-12"><label class="form-label">Justification obligatoire</label><textarea name="justification" rows="3" class="form-control" required minlength="5">{{ old('justification') }}</textarea></div>
  <div class="col-12"><button class="btn btn-upl py-2">Enregistrer la correction</button></div></form></div></div></div>
@endsection
