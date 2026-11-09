@extends('layouts.app')
@section('title', 'Inscription — UPL PRESENCE')
@section('content')
<div class="row justify-content-center"><div class="col-lg-8"><div class="card p-4">
  <h4 class="mb-3">Créer un compte stagiaire</h4>
  <form method="post" action="/inscription" class="row g-3">@csrf
    @foreach([['nom','Nom'],['postnom','Postnom'],['prenom','Prénom'],['matricule','Matricule'],['telephone','Téléphone'],['email','Adresse e-mail','email'],['entreprise','Entreprise / institution d\'accueil']] as $f)
      <div class="col-md-{{ in_array($f[0],['entreprise']) ? 12 : 6 }}"><label class="form-label">{{ $f[1] }}</label>
        <input name="{{ $f[0] }}" type="{{ $f[2] ?? 'text' }}" value="{{ old($f[0]) }}" class="form-control" required @if($f[0]==='entreprise') list="ents" @endif></div>
    @endforeach
    <datalist id="ents">@foreach($entreprises as $e)<option value="{{ $e->nom }}">@endforeach</datalist>
    <div class="col-md-6"><label class="form-label">Sexe</label><select name="sexe" class="form-select" required><option value="">—</option><option value="M" @selected(old('sexe')==='M')>Masculin</option><option value="F" @selected(old('sexe')==='F')>Féminin</option></select></div>
    <div class="col-md-6"><label class="form-label">Promotion</label><select name="promotion_id" class="form-select" required><option value="">—</option>@foreach($promotions as $p)<option value="{{ $p->id }}" @selected(old('promotion_id')==$p->id)>{{ $p->nom }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Faculté</label><select name="faculte_id" id="fac" class="form-select" required><option value="">—</option>@foreach($facultes as $f)<option value="{{ $f->id }}" @selected(old('faculte_id')==$f->id)>{{ $f->nom }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Filière</label><select name="filiere_id" id="fil" class="form-select" required><option value="">—</option>
      @foreach($facultes as $f)@foreach($f->filieres as $fi)<option value="{{ $fi->id }}" data-f="{{ $f->id }}" @selected(old('filiere_id')==$fi->id)>{{ $fi->nom }}</option>@endforeach @endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Mot de passe</label><input type="password" name="password" class="form-control" required minlength="8"><div class="form-text">8 caractères min., lettres et chiffres.</div></div>
    <div class="col-md-6"><label class="form-label">Confirmation</label><input type="password" name="password_confirmation" class="form-control" required></div>
    <div class="col-12"><button class="btn btn-upl w-100">Créer mon compte</button></div>
  </form></div></div></div>
@endsection
@push('scripts')<script>
const fac=document.getElementById('fac'),fil=document.getElementById('fil');
function f(){[...fil.options].forEach(o=>{if(!o.value)return;o.hidden=o.dataset.f!==fac.value;if(o.hidden&&o.selected)fil.value='';});}
fac.addEventListener('change',f);f();
</script>@endpush
