@if (session('ok'))
    <div class="aviso ok" role="status">{{ session('ok') }}</div>
@endif
@if ($errors->any())
    <div class="aviso error" role="alert">
        <b>Revise los datos:</b>
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
