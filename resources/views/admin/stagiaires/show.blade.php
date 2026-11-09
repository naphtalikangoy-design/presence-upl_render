@extends('layouts.admin')
@section('title', 'Fiche stagiaire')
@section('content')
<div class="row g-3"><div class="col-lg-4"><div class="card p-4">
  <div class="d-flex gap-3 align-items-center mb-2">@if($st->photo)<img class="avatar" src="{{ asset($st->photo) }}">@else<div class="avatar">{{ mb_strtoupper(mb_substr($st->prenom,0,1).mb_substr($st->nom,0,1)) }}</div>@endif
    <div><h5 class="mb-0">{{ $st->nom_complet }}</h5><span class="pill {{ ['actif'=>'ok','en_attente'=>'wait','rejete'=>'ko','desactive'=>'ko'][$st->user->statut] }}">{{ str_replace('_',' ',$st->user->statut) }}</span></div></div>
  <dl class="row small mb-3"><dt class="col-5">Matricule</dt><dd class="col-7">{{ $st->user->matricule }}</dd><dt class="col-5">E-mail</dt><dd class="col-7">{{ $st->user->email }}</dd><dt class="col-5">Téléphone</dt><dd class="col-7">{{ $st->telephone }}</dd>
  <dt class="col-5">Faculté</dt><dd class="col-7">{{ $st->faculte->nom }}</dd><dt class="col-5">Filière</dt><dd class="col-7">{{ $st->filiere->nom }}</dd><dt class="col-5">Promotion</dt><dd class="col-7">{{ $st->promotion->nom }}</dd>
  <dt class="col-5">Accueil</dt><dd class="col-7">{{ $st->entreprise->nom ?? '—' }}</dd><dt class="col-5">Stage</dt><dd class="col-7">{{ $st->date_debut_stage?->format('d/m/Y') ?? '…' }} → {{ $st->date_fin_stage?->format('d/m/Y') ?? '…' }}</dd>
  <dt class="col-5">Présences</dt><dd class="col-7">{{ $total }} (dont {{ $retards }} retards)</dd></dl>
  <div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-secondary" href="/admin/stagiaires/{{ $st->id }}/edit">Modifier</a>
    @if($st->user->statut==='en_attente')<form method="post" action="/admin/stagiaires/{{ $st->id }}/valider">@csrf<button class="btn btn-sm btn-success">Valider</button></form><form method="post" action="/admin/stagiaires/{{ $st->id }}/rejeter">@csrf<button class="btn btn-sm btn-outline-danger">Rejeter</button></form>@endif
    @if(in_array($st->user->statut,['rejete','desactive']))<form method="post" action="/admin/stagiaires/{{ $st->id }}/reactiver">@csrf<button class="btn btn-sm btn-success">Réactiver</button></form>@endif
    @if($st->user->statut==='actif')<form method="post" action="/admin/stagiaires/{{ $st->id }}" onsubmit="return confirm('Désactiver ce compte ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-warning">Désactiver</button></form>@endif
    <form method="post" action="/admin/stagiaires/{{ $st->id }}" onsubmit="return confirm('SUPPRIMER définitivement le compte ET toutes ses présences ?')">@csrf @method('DELETE')<input type="hidden" name="definitif" value="1"><button class="btn btn-sm btn-outline-danger">Supprimer définitivement</button></form></div>
</div></div>
<div class="col-lg-8"><div class="card p-3"><h6>30 dernières opérations de pointage</h6><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Date</th><th>Type</th><th>Heure</th><th>Statut</th><th>Bâtiment</th><th></th></tr></thead><tbody>
@forelse($presences as $p)<tr><td>{{ $p->date->format('d/m/Y') }}</td><td>{{ $p->type }}</td><td>{{ $p->pointe_a->format('H:i') }}</td><td>{{ $p->statut }}{{ $p->corrige ? ' ✎' : '' }}</td><td>{{ $p->batiment->nom ?? '—' }}</td><td><a href="/admin/presences/{{ $p->id }}/edit">détail</a></td></tr>
@empty<tr><td colspan="6" class="text-muted text-center py-3">Aucune présence.</td></tr>@endforelse</tbody></table></div></div></div></div>
@endsection
