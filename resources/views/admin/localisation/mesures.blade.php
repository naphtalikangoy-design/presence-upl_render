@extends('layouts.admin')
@section('title', 'Mesures RSSI')
@push('head')<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>@endpush
@section('content')
<form class="card p-3 mb-3 row g-2 flex-row" method="get"><div class="col-md-4"><select name="ap" class="form-select"><option value="">Tous les AP</option>@foreach($aps as $a)<option value="{{ $a->id }}" @selected(request('ap')==$a->id)>{{ $a->nom }}</option>@endforeach</select></div>
<div class="col-md-3"><select name="minutes" class="form-select">@foreach([15=>'15 min',60=>'1 heure',360=>'6 heures',1440=>'24 heures'] as $k=>$l)<option value="{{ $k }}" @selected($minutes==$k)>{{ $l }}</option>@endforeach</select></div><div class="col-md-2 d-grid"><button class="btn btn-upl py-2">Filtrer</button></div></form>
<div class="row g-3"><div class="col-lg-5"><div class="card p-3"><h6>RSSI moyen par AP (dBm)</h6>@if($moy->count())<canvas id="c"></canvas>@else<div class="text-muted small">Aucune mesure sur la période. Le connecteur actif fournit-il le RSSI ? (voir État du réseau)</div>@endif</div></div>
<div class="col-lg-7"><div class="card p-3"><div class="table-responsive" style="max-height:480px"><table class="table table-sm align-middle"><thead><tr><th>Heure</th><th>AP</th><th>RSSI</th><th>Bande</th><th>Source</th></tr></thead><tbody>
@forelse($rows as $m)<tr><td>{{ $m->mesure_at->format('d/m H:i:s') }}</td><td>{{ $m->pointAcces->nom ?? $m->ap_identifiant }}</td><td>{{ $m->rssi_dbm }} dBm</td><td>{{ $m->bande ?? '—' }}</td><td>{{ $m->source }} @if($m->est_demo)<span class="pill wait">simulé</span>@endif</td></tr>
@empty<tr><td colspan="5" class="text-center text-muted py-3">Aucune mesure.</td></tr>@endforelse</tbody></table></div></div></div></div>
@endsection
@push('scripts')@if($moy->count())<script>const M=@json($moy);new Chart(document.getElementById('c'),{type:'bar',data:{labels:Object.keys(M),datasets:[{label:'dBm',data:Object.values(M),backgroundColor:'#0b1f3a'}]},options:{scales:{y:{suggestedMin:-100,suggestedMax:-30}}}});</script>@endif @endpush
