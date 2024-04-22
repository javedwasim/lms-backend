<style>
    .pagi_active{
        background: #D3D3D3 !important;
    }
    .blocks:first-child {
        border-radius: 10px 0 0 10px;
    }
    .blocks:last-child {
        border-radius: 0 10px 10px 0;
    } 
</style>
@if ($paginator->hasPages())
<div id="container mt-3">
    <div class="pagination">
        @if ($paginator->onFirstPage())
            <a href="javascript:void(0);" class="blocks" style="background:#D3D3D3;">«</a>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="blocks" style="background:#D3D3D3;">«</a>
        @endif

        @foreach ($elements as $element)

            @if (is_string($element))
                <a href="javascript:void(0);" class="blocks">{{ $element }}</a>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <a href="javascript:void(0);" class="blocks pagi_active">{{ $page }}</a>
                    @else
                        <a href="{{ $url }}" class="blocks">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="blocks" style="background:#D3D3D3;">»</a>
        @else
            <a href="javascript:void(0);" class="blocks" style="background:#D3D3D3;">»</a>
        @endif
    </div>
</div>
@endif
