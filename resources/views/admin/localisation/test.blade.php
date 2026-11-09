@extends('layouts.admin')
@section('title', 'Tests de localisation')
@section('content')
<div class="card p-4 mb-3"><form method="post" action="/admin/tests-localisation" class="row g-2">@csrf
  <div class="col-md-6"><input name="username" value="{{ old('username') }}" class="form-control" placeholder="Matricule / identifiant de la session Wi-Fi à analyser" required></div><div class="col-md-3 d-grid"><button class="btn btn-upl py-2"><i class="bi bi-bullseye"></i> Analyser</button></div></form>
  <div class="small text-muted mt-2">Applique l'algorithme réel (sans enregistrer de présence) à la session active de cet identifiant.</div></div>
@if($res)
  @if(isset($res['erreur']))<div class="alert alert-warning">{!! $res['erreur'] !!}</div>@endif
  @if(isset($res['resultat']))@php $x=$res['resultat']; @endphp
  <div class="card p-4"><h6>Résultat — {{ $res['batiment']->nom }}</h6>
    <div class="row g-3 mb-2"><div class="col-md-3"><div class="stat card"><div class="n">{{ $x->classe }}</div><div class="l">Classe estimée</div></div></div>
    <div class="col-md-3"><div class="stat card"><div class="n">{{ round($x->confiance*100) }} %</div><div class="l">Confiance (seuil {{ round($res['seuil']*100) }} %)</div></div></div>
    <div class="col-md-3"><div class="stat card"><div class="n" style="font-size:1.2rem">{{ $x->methode }}</div><div class="l">Méthode</div></div></div>
    <div class="col-md-3"><div class="stat card"><div class="n" style="font-size:1.2rem">{{ $x->assurance }}</div><div class="l">Assurance</div></div></div></div>
    <ul class="small mb-2"><li>Session : {{ $res['session']->username }} · AP {{ $res['session']->pointAcces->nom ?? '?' }} @if($res['session']->est_demo)<span class="pill wait">SIMULATION</span>@endif</li>
      <li>Signal suffisant : {{ $x->signalSuffisant ? 'oui' : 'NON' }} · Mesures instables : {{ $x->instable ? 'OUI' : 'non' }} · Empreintes actives du bâtiment : {{ $res['nbFps'] }}</li>
      <li>Mesures médianes : @forelse($x->details['medians'] ?? [] as $k=>$v)<code>{{ substr($k,-4) }}</code> {{ $v }} dBm &nbsp;@empty aucune @endforelse</li>
      @if(isset($x->details['distance_min_db']))<li>Distance minimale à une empreinte : {{ $x->details['distance_min_db'] }} dB</li>@endif</ul>
    <div class="alert alert-secondary small mb-0">Décision de pointage correspondante :
      @if($x->methode==='aucune') aucune mesure — {{ config('upl.localisation_policy')==='strict' ? 'LOCALISATION NON CONFIRMÉE' : 'AP + GPS (assurance faible)' }}
      @elseif(!$x->signalSuffisant) SIGNAL INSUFFISANT
      @elseif(in_array($x->methode,['calibration_insuffisante','hors_reference'])) LOCALISATION NON CONFIRMÉE ({{ $x->methode }})
      @elseif($x->classe==='interieur' && $x->confiance >= $res['seuil']) intérieur confirmé (le GPS est ensuite contrôlé)
      @elseif($x->classe==='exterieur' && $x->confiance >= $res['seuil']) HORS DU BÂTIMENT
      @else LOCALISATION NON CONFIRMÉE (confiance insuffisante) @endif</div></div>
  @endif
@endif
@endsection
