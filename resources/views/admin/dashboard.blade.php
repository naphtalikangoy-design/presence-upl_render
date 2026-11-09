@extends('layouts.admin')
@section('title', 'Tableau de bord')
@push('head')<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>@endpush
@section('content')
<div class="row g-3 mb-3">
  @foreach([['Stagiaires inscrits',$s['total'],'people'],['Présences du jour',$s['presents'],'check-circle'],['Absences du jour',$s['absences'],'x-circle'],['Retards du jour',$s['retards'],'alarm'],['Comptes en attente',$s['attente'],'hourglass-split']] as [$l,$n,$i])
  <div class="col-6 col-lg"><div class="card stat"><div class="d-flex justify-content-between"><div><div class="n">{{ $n }}</div><div class="l">{{ $l }}</div></div><i class="bi bi-{{ $i }} gold fs-3"></i></div>
    @if($l==='Comptes en attente' && $n)<a href="/admin/stagiaires?statut=en_attente" class="small">Traiter →</a>@endif</div></div>
  @endforeach
</div>
@unless($s['ouvrable'])<div class="alert alert-secondary">Aujourd'hui n'est pas un jour ouvrable : aucune absence n'est comptée.</div>@endunless
<div class="row g-3">
  <div class="col-lg-8"><div class="card p-3"><div class="d-flex justify-content-between"><h6>Évolution des présences</h6>
    <div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary active" data-p="sem">Semaines</button><button class="btn btn-outline-secondary" data-p="mois">Mois</button></div></div><canvas id="evo" height="110"></canvas></div></div>
  <div class="col-lg-4"><div class="card p-3"><h6>Par faculté (30 jours)</h6><canvas id="fac"></canvas></div></div>
  <div class="col-lg-6"><div class="card p-3"><h6>Par bâtiment (30 jours)</h6><canvas id="bat" height="120"></canvas></div></div>
</div>
@endsection
@push('scripts')<script>
const S=@json($s);const gold='#c9a227',navy='#0b1f3a';
const evo=new Chart(document.getElementById('evo'),{type:'bar',data:{labels:[],datasets:[{label:'Présences',backgroundColor:navy,data:[]},{label:'Retards',backgroundColor:gold,data:[]}]},options:{responsive:true}});
function set(k){const a=k==='sem'?S.semaines:S.mois;evo.data.labels=a.map(x=>x.label);evo.data.datasets[0].data=a.map(x=>x.presents);evo.data.datasets[1].data=a.map(x=>x.retards);evo.update();}
set('sem');document.querySelectorAll('[data-p]').forEach(b=>b.onclick=()=>{document.querySelectorAll('[data-p]').forEach(x=>x.classList.remove('active'));b.classList.add('active');set(b.dataset.p);});
const pal=[navy,gold,'#2fa35b','#d94545','#4a78c2','#8a6bbf','#17a2b8'];
new Chart(document.getElementById('fac'),{type:'doughnut',data:{labels:Object.keys(S.parFaculte),datasets:[{data:Object.values(S.parFaculte),backgroundColor:pal}]}});
new Chart(document.getElementById('bat'),{type:'bar',data:{labels:Object.keys(S.parBatiment),datasets:[{label:'Présences',data:Object.values(S.parBatiment),backgroundColor:navy}]},options:{indexAxis:'y'}});
</script>@endpush
