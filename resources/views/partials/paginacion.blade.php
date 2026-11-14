@if ($paginator->hasPages())
    <nav aria-label="Paginación">
        <ul class="paginacion">
            @if ($paginator->onFirstPage())
                <li class="deshabilitado"><span>« Anterior</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">« Anterior</a></li>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="deshabilitado"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="actual" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente »</a></li>
            @else
                <li class="deshabilitado"><span>Siguiente »</span></li>
            @endif
        </ul>
        <small>Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</small>
    </nav>
@endif
