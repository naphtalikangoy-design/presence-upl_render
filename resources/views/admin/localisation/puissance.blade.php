@extends('layouts.admin')
@section('title', 'Puissance d\'émission')
@section('content')
<div class="alert {{ $peut ? 'alert-info' : 'alert-warning' }}">Connecteur actif : <strong>{{ $connecteur }}</strong>. @if(!$peut)Ce connecteur <strong>ne permet pas</strong> de modifier la puissance : les formulaires sont désactivés. Aucune modification n'est jamais simulée. Il faut un contrôleur dont l'administrateur réseau fournit l'API (voir README §5).@else Les modifications sont envoyées au contrôleur ; elles ne sont enregistrées ici que si le matériel les confirme.@endif Chaque AP doit déclarer ses capacités (<code>tx_power</code>, <code>tx_min</code>, <code>tx_max</code>) dans « Access Points ». Recalibrez après toute modification.</div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>AP</th><th>Bâtiment</th><th>Puissance connue</th><th>Canaux (info)</th><th>Capacités</th><th>Modifier</th></tr></thead><tbody>
@forelse($aps as $a)@php $c=$a->capacites ?? []; $ok = $peut && ($c['tx_power'] ?? false); @endphp<tr><td>{{ $a->nom }}</td><td>{{ $a->batiment->nom }}</td><td>{{ $a->puissance_dbm !== null ? $a->puissance_dbm.' dBm' : 'inconnue' }}</td><td class="small">2,4 GHz : {{ $a->canal_2g ?? '—' }}<br>5 GHz : {{ $a->canal_5g ?? '—' }}</td>
<td class="small">{{ ($c['tx_power'] ?? false) ? 'TX '.$c['tx_min'].'–'.$c['tx_max'].' dBm' : 'non déclarées' }}</td>
<td><form method="post" action="/admin/puissance/{{ $a->id }}" class="d-flex gap-1">@csrf<input type="number" name="puissance_dbm" class="form-control form-control-sm" style="width:80px" placeholder="dBm" @disabled(!$ok) required>
<select name="bande" class="form-select form-select-sm" style="width:90px" @disabled(!$ok)><option value="2.4">2,4 GHz</option><option value="5">5 GHz</option></select><input type="number" name="canal" class="form-control form-control-sm" style="width:80px" placeholder="canal" @disabled(!$ok)>
<button class="btn btn-sm btn-upl" style="padding:.3rem .8rem" @disabled(!$ok)>Appliquer</button></form></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted py-3">Aucun AP.</td></tr>@endforelse</tbody></table></div></div>
@endsection
