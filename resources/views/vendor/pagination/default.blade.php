@if ($paginator->hasPages())
    <ul class="pagination">
        @if ($paginator->onFirstPage())
            <li class="paginate_button page-item previous disabled" id="DataTables_Table_0_previous">
                <a href="javascript:void(0);" aria-controls="DataTables_Table_0" data-dt-idx="0" tabindex="0" class="page-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="feather feather-arrow-left">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
            </li>
        @else
            <li class="paginate_button page-item previous" id="DataTables_Table_0_previous">
                <a href="{{ $paginator->previousPageUrl() }}" aria-controls="DataTables_Table_0" data-dt-idx="0" tabindex="0" class="page-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="feather feather-arrow-left">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
            </li>
        @endif

        @foreach ($elements as $element)

            @if (is_string($element))
                <li class="disabled"><span><a href="javascript:void(0);">{{ $element }}</a></span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="paginate_button page-item active">
                            <a href="javascript:void(0);" aria-controls="DataTables_Table_0" data-dt-idx="1" tabindex="0" class="page-link">{{ $page }}</a>
                        </li>
                    @else
                        <li class="paginate_button page-item ">
                            <a href="{{ $url }}" aria-controls="DataTables_Table_0" data-dt-idx="2" tabindex="0" class="page-link">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li class="paginate_button page-item next" id="DataTables_Table_0_next">
                <a href="{{ $paginator->nextPageUrl() }}"
                   aria-controls="DataTables_Table_0"
                   data-dt-idx="8" tabindex="0"
                   class="page-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="feather feather-arrow-right">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </li>
        @else
            <li class="paginate_button page-item next disabled" id="DataTables_Table_0_next">
                <a href="javascript:void(0);"
                   aria-controls="DataTables_Table_0"
                   data-dt-idx="8" tabindex="0"
                   class="page-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                         class="feather feather-arrow-right">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </li>
        @endif
    </ul>
@endif
