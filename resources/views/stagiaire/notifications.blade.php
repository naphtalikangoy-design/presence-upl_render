@extends('layouts.app')
@section('title', 'Notifications — UPL PRESENCE')
@section('content')
<div class="d-flex justify-content-between mb-3"><h4>Notifications</h4><form method="post" action="/stagiaire/notifications/lire">@csrf<button class="btn btn-outline-secondary btn-sm">Tout marquer comme lu</button></form></div>
<div class="card">@forelse($n as $x)<div class="p-3 border-bottom {{ $x->lu_at ? '' : 'bg-light' }}"><div class="d-flex justify-content-between"><strong>{{ $x->titre }}</strong><small class="text-muted">{{ $x->created_at->format('d/m/Y H:i') }}</small></div><div class="small">{{ $x->message }}</div></div>
@empty<div class="p-4 text-center text-muted">Aucune notification.</div>@endforelse</div><div class="mt-3">{{ $n->links() }}</div>
@endsection
