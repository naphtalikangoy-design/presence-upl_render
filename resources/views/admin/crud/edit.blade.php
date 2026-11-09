@extends('layouts.admin')
@section('title', 'Modifier — '.$c['singulier'])
@section('content')
<div class="card p-4"><form method="post" action="/admin/{{ $slug }}/{{ $m->id }}" class="row g-3">@csrf @method('PUT')
  @include('admin.crud._fields')<div class="col-12 d-flex gap-2"><button class="btn btn-upl py-2">Enregistrer</button><a href="/admin/{{ $slug }}" class="btn btn-outline-secondary">Annuler</a></div></form></div>
@endsection
