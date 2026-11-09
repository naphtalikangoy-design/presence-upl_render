@extends('layouts.admin')
@section('title', 'Zones autorisées')
@push('head')<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">@endpush
@section('content')
<div class="alert alert-info small">Le GPS ne distingue pas précisément l'intérieur de l'extérieur : le périmètre ci-dessous est un contrôle <strong>complémentaire</strong> à la localisation Wi-Fi. Cliquez sur la carte pour renseigner les coordonnées.</div>
<div class="row g-3"><div class="col-lg-7"><div class="card p-2"><div id="map"></div></div></div>
<div class="col-lg-5"><div class="card p-3"><h6>{{ $edit ? 'Modifier la zone' : 'Nouvelle zone GPS' }}</h6>
<form method="post" action="{{ $edit ? "/admin/zones/{$edit->id}" : '/admin/zones' }}" class="row g-2">@csrf @if($edit)@method('PUT')@endif
  <div class="col-12"><select name="batiment_id" class="form-select" required><option value="">Bâtiment…</option>@foreach($batiments as $b)<option value="{{ $b->id }}" @selected(old('batiment_id',$edit?->batiment_id)==$b->id)>{{ $b->nom }}</option>@endforeach</select></div>
  <div class="col-12"><input name="nom" value="{{ old('nom',$edit?->nom) }}" class="form-control" placeholder="Nom de la zone" required></div>
  <div class="col-6"><input id="lat" name="latitude" value="{{ old('latitude',$edit?->latitude) }}" class="form-control" placeholder="Latitude" required></div>
  <div class="col-6"><input id="lng" name="longitude" value="{{ old('longitude',$edit?->longitude) }}" class="form-control" placeholder="Longitude" required></div>
  <div class="col-6"><input id="ray" type="number" name="rayon_m" min="5" max="2000" value="{{ old('rayon_m',$edit?->rayon_m ?? 50) }}" class="form-control" placeholder="Rayon (m)" required></div>
  <div class="col-6 pt-2"><div class="form-check"><input type="hidden" name="actif" value="0"><input class="form-check-input" type="checkbox" name="actif" value="1" id="act" @checked(old('actif',$edit?->actif ?? true))><label class="form-check-label" for="act">Zone active</label></div></div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-upl py-2">Enregistrer</button>@if($edit)<a href="/admin/zones" class="btn btn-outline-secondary">Annuler</a>@endif</div></form></div></div></div>
<div class="card p-3 mt-3"><div class="table-responsive"><table class="table align-middle table-sm"><thead><tr><th>Bâtiment</th><th>Zone</th><th>Latitude</th><th>Longitude</th><th>Rayon</th><th>État</th><th></th></tr></thead><tbody>
@forelse($zones as $z)<tr><td>{{ $z->batiment->nom }}</td><td>{{ $z->nom }}</td><td>{{ $z->latitude }}</td><td>{{ $z->longitude }}</td><td>{{ $z->rayon_m }} m</td><td><span class="pill {{ $z->actif?'ok':'wait' }}">{{ $z->actif?'active':'inactive' }}</span></td>
<td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="/admin/zones?edit={{ $z->id }}"><i class="bi bi-pencil"></i></a>
<form class="d-inline" method="post" action="/admin/zones/{{ $z->id }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
@empty<tr><td colspan="7" class="text-center text-muted py-3">Aucune zone : sans zone active, aucun pointage ne peut être validé par le GPS.</td></tr>@endforelse</tbody></table></div></div>

<div class="card p-3 mt-3"><h6>Zones intérieures (zones autorisées pour la calibration Wi-Fi)</h6>
<form method="post" action="/admin/zones-interieures" class="row g-2 mb-3">@csrf
  <div class="col-md-3"><select name="batiment_id" class="form-select" required><option value="">Bâtiment…</option>@foreach($batiments as $b)<option value="{{ $b->id }}">{{ $b->nom }}</option>@endforeach</select></div>
  <div class="col-md-4"><input name="nom" class="form-control" placeholder="Ex. Rez-de-chaussée, Salle informatique" required></div>
  <div class="col-md-2"><input name="etage" class="form-control" placeholder="Étage"></div>
  <div class="col-md-2 pt-2"><div class="form-check"><input type="hidden" name="autorise" value="0"><input class="form-check-input" type="checkbox" name="autorise" value="1" id="aut" checked><label class="form-check-label" for="aut">Autorisée</label></div></div>
  <div class="col-md-1 d-grid"><button class="btn btn-upl py-2">+</button></div></form>
<table class="table table-sm align-middle mb-0"><tbody>@forelse($interieures as $z)<tr><td>{{ $z->batiment->nom }}</td><td>{{ $z->nom }}</td><td>{{ $z->etage }}</td><td><span class="pill {{ $z->autorise?'ok':'ko' }}">{{ $z->autorise?'autorisée':'non autorisée' }}</span></td>
<td class="text-end"><form method="post" action="/admin/zones-interieures/{{ $z->id }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>@empty<tr><td class="text-muted">Aucune zone intérieure.</td></tr>@endforelse</tbody></table></div>
@endsection
@push('scripts')<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script>
const Z=@json($zones->map(fn($z)=>['n'=>$z->nom,'b'=>$z->batiment->nom,'lat'=>$z->latitude,'lng'=>$z->longitude,'r'=>$z->rayon_m,'a'=>$z->actif]));
const map=L.map('map').setView(Z.length?[Z[0].lat,Z[0].lng]:[-11.6876,27.5026],Z.length?18:15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap'}).addTo(map);
Z.forEach(z=>L.circle([z.lat,z.lng],{radius:z.r,color:z.a?'#0b1f3a':'#999',fillColor:z.a?'#c9a227':'#ccc',fillOpacity:.3}).addTo(map).bindPopup('<b>'+z.b.replace(/</g,'&lt;')+'</b><br>'+z.n.replace(/</g,'&lt;')+'<br>Rayon '+z.r+' m'));
let cur=null;function draw(){const la=parseFloat(lat.value),ln=parseFloat(lng.value),r=parseFloat(ray.value)||50;if(isNaN(la)||isNaN(ln))return;if(cur)map.removeLayer(cur);cur=L.circle([la,ln],{radius:r,color:'#d94545',dashArray:'4'}).addTo(map);}
map.on('click',e=>{lat.value=e.latlng.lat.toFixed(7);lng.value=e.latlng.lng.toFixed(7);draw();});[lat,lng,ray].forEach(i=>i.addEventListener('input',draw));draw();
</script>@endpush
