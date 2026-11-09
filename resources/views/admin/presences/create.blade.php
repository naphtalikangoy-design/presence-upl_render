@extends('layouts.admin')
@section('title', 'Saisie manuelle de présence')
@section('content')
<div class="card p-4"><div class="alert alert-info small">Les saisies manuelles sont marquées « manuel », sans coordonnées GPS ni vérification Wi-Fi, et sont journalisées.</div>
<form method="post" action="/admin/presences" class="row g-3">@csrf
  <div class="col-md-4"><label class="form-label">Matricule du stagiaire</label><input name="matricule" value="{{ old('matricule') }}" class="form-control" required></div>
  <div class="col-md-4"><label class="form-label">Type</label><select name="type" class="form-select"><option value="arrivee">Arrivée</option><option value="depart">Départ</option></select></div>
  <div class="col-md-4"><label class="form-label">Date et heure</label><input type="datetime-local" name="pointe_a" value="{{ old('pointe_a') }}" class="form-control" required></div>
  <div class="col-md-4"><label class="form-label">Statut</label><select name="statut" class="form-select"><option value="present">Présent</option><option value="retard">Retard</option></select></div>
  <div class="col-md-4"><label class="form-label">Minutes de retard</label><input type="number" name="retard_minutes" min="0" value="{{ old('retard_minutes',0) }}" class="form-control"></div>
  <div class="col-md-4"><label class="form-label">Bâtiment</label><select name="batiment_id" class="form-select"><option value="">—</option>@foreach($batiments as $b)<option value="{{ $b->id }}">{{ $b->nom }}</option>@endforeach</select></div>
  <div class="col-12"><label class="form-label">Justification obligatoire</label><textarea name="justification" rows="2" class="form-control" required>{{ old('justification') }}</textarea></div>
  <div class="col-12"><button class="btn btn-upl py-2">Ajouter</button></div></form></div>
@endsection
