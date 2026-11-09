@extends('layouts.admin')
@section('title', $c['titre'])
@section('content')
<div class="card p-3 mb-3"><a class="fw-semibold text-decoration-none" data-bs-toggle="collapse" href="#nouveau"><i class="bi bi-plus-circle gold"></i> Ajouter : {{ $c['singulier'] }}</a>
  <div class="collapse {{ $errors->any() ? 'show' : '' }}" id="nouveau"><form method="post" action="/admin/{{ $slug }}" class="row g-3 mt-1">@csrf
    @include('admin.crud._fields', ['m' => null])<div class="col-12"><button class="btn btn-upl py-2">Enregistrer</button></div></form></div></div>
<div class="card p-3"><div class="table-responsive"><table class="table align-middle"><thead><tr>
  @foreach($c['colonnes'] as $col)@php $fl = collect($c['champs'])->first(fn($f)=>$f[0]===$col) ?? collect($c['champs'])->first(fn($f)=>$f[0]===explode('.',$col)[0].'_id'); @endphp<th>{{ $fl[1] ?? ucfirst(str_replace('_',' ',$col)) }}</th>@endforeach<th></th></tr></thead><tbody>
  @forelse($rows as $row)<tr>@foreach($c['colonnes'] as $col)@php $v = data_get($row,$col); @endphp<td>
    @if(is_bool($v))<span class="pill {{ $v?'ok':'wait' }}">{{ $v?'Oui':'Non' }}</span>@elseif($v instanceof \Carbon\Carbon){{ $v->format('d/m/Y H:i') }}@elseif(is_string($v) && preg_match('/^\d\d:\d\d:\d\d$/',$v)){{ substr($v,0,5) }}@else{{ $v ?? '—' }}@endif</td>@endforeach
    <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="/admin/{{ $slug }}/{{ $row->id }}/edit"><i class="bi bi-pencil"></i></a>
      <form class="d-inline" method="post" action="/admin/{{ $slug }}/{{ $row->id }}" onsubmit="return confirm('Supprimer cet élément ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
  @empty<tr><td colspan="{{ count($c['colonnes'])+1 }}" class="text-center text-muted py-4">Aucun élément.</td></tr>@endforelse</tbody></table></div>{{ $rows->links() }}</div>
@endsection
