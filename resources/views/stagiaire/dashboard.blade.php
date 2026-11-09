@extends('layouts.app')
@section('title', 'Tableau de bord — UPL PRESENCE')
@section('content')
@if($u->statut === 'en_attente')<div class="alert alert-warning"><i class="bi bi-hourglass-split"></i> Votre compte est en attente de validation : le pointage sera disponible après activation par l'administration.</div>@endif
<div class="row g-3">
  <div class="col-lg-4"><div class="card p-4">
    <div class="d-flex gap-3 align-items-center">
      @if($st->photo)<img class="avatar" src="{{ asset($st->photo) }}" alt="">@else<div class="avatar">{{ mb_strtoupper(mb_substr($st->prenom,0,1).mb_substr($st->nom,0,1)) }}</div>@endif
      <div><h5 class="mb-0">{{ $st->nom_complet }}</h5><div class="text-muted small">Matricule {{ $u->matricule }}</div></div></div>
    <hr><div class="small"><div><i class="bi bi-bank gold"></i> {{ $st->faculte->nom }} — {{ $st->filiere->nom }}</div>
      <div><i class="bi bi-mortarboard gold"></i> Promotion {{ $st->promotion->nom }}</div>
      <div><i class="bi bi-briefcase gold"></i> {{ $st->entreprise->nom ?? 'Entreprise non renseignée' }}</div>
      @if($st->date_debut_stage)<div><i class="bi bi-calendar-range gold"></i> Stage du {{ $st->date_debut_stage->format('d/m/Y') }} au {{ $st->date_fin_stage?->format('d/m/Y') ?? '…' }}</div>@endif</div>
  </div></div>
  <div class="col-lg-8"><div class="card p-4 h-100">
    <div class="d-flex justify-content-between flex-wrap"><div><div class="text-muted small">{{ now()->translatedFormat('l d F Y') }}</div><div class="fs-2 fw-bold" id="clock">{{ now()->format('H:i:s') }}</div></div>
      <div class="text-end"><div class="text-muted small">Statut de présence du jour</div>
        @if(!$ouvrable)<span class="pill wait">Jour non ouvrable</span>
        @elseif($arr)<span class="pill {{ $arr->statut === 'retard' ? 'wait' : 'ok' }}">{{ $arr->statut === 'retard' ? 'En retard ('.$arr->retard_minutes.' min)' : 'Présent' }}</span><div class="small mt-1">Arrivée {{ $arr->pointe_a->format('H:i') }}@if($arr->batiment) · {{ $arr->batiment->nom }}@endif @if($dep)· Départ {{ $dep->pointe_a->format('H:i') }}@endif</div>
        @else<span class="pill ko">Pas encore pointé</span>@endif</div></div>
    <hr>
    <div class="row text-center g-2 mb-3">
      <div class="col-4"><div class="stat card"><div class="n">{{ $presMois }}</div><div class="l">Présences ce mois</div></div></div>
      <div class="col-4"><div class="stat card"><div class="n">{{ $retMois }}</div><div class="l">Retards ce mois</div></div></div>
      <div class="col-4"><div class="stat card"><div class="n" id="conn">…</div><div class="l">Connexion · <span id="geo">GPS …</span></div></div></div>
    </div>
    <a href="/stagiaire/pointage" class="btn btn-upl btn-lg w-100"><i class="bi bi-geo-alt"></i> ACCÉDER AU POINTAGE</a>
    <div class="small text-muted mt-2">La confirmation du Wi-Fi officiel et de la zone est effectuée par le serveur sur la page de pointage.</div>
  </div></div>
</div>
@if($notifs->count())<div class="card p-3 mt-3"><h6>Dernières notifications</h6>@foreach($notifs as $n)<div class="small border-top py-2"><strong>{{ $n->titre }}</strong> — {{ $n->message }} <span class="text-muted">({{ $n->created_at->diffForHumans() }})</span></div>@endforeach</div>@endif
@endsection
@push('scripts')<script>
setInterval(()=>clock.textContent=new Date().toLocaleTimeString('fr-FR'),1000);
conn.textContent=navigator.onLine?'En ligne':'Hors ligne';
if(navigator.permissions){navigator.permissions.query({name:'geolocation'}).then(p=>{const s=()=>geo.textContent='GPS '+({granted:'autorisé',denied:'refusé',prompt:'à autoriser'}[p.state]);s();p.onchange=s;}).catch(()=>geo.textContent='GPS ?');}else geo.textContent='GPS ?';
</script>@endpush
