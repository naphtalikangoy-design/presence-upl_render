@if(session('ok'))<div class="alert alert-success alert-dismissible fade show">{{ session('ok') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('err'))<div class="alert alert-danger alert-dismissible fade show">{{ session('err') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
