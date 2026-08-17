@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="payments-mobile-custom-nav" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
        <div style="margin-bottom: 12px !important; font-size: 13px !important; color: var(--muted) !important; text-align: center;">
            Showing <strong style="font-weight: 800 !important; color: var(--ink) !important;">{{ $paginator->firstItem() }}</strong> to <strong style="font-weight: 800 !important; color: var(--ink) !important;">{{ $paginator->lastItem() }}</strong> of <strong style="font-weight: 800 !important; color: var(--ink) !important;">{{ $paginator->total() }}</strong> results
        </div>
        <div style="display: inline-flex; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" aria-label="{{ __('Pagination Navigation') }}">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <span class="inline-flex items-center px-3 py-2 text-sm font-bold text-gray-400 bg-gray-100 border border-gray-300 rounded-l-md leading-5 cursor-not-allowed" style="font-weight: 800 !important;" aria-hidden="true">&lsaquo;</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-3 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-l-md leading-5 hover:bg-gray-100 transition ease-in-out duration-150" style="font-weight: 800 !important;" aria-label="{{ __('pagination.previous') }}">&lsaquo;</a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true">
                        <span class="inline-flex items-center px-3 py-2 -ml-px text-sm font-bold text-gray-700 bg-white border border-gray-300 cursor-default leading-5" style="font-weight: 800 !important;">{{ $element }}</span>
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page">
                                <span class="inline-flex items-center px-3.5 py-2 -ml-px text-sm font-bold bg-amber-400 border border-amber-500 cursor-default leading-5" style="background: var(--ch-yellow) !important; color: var(--ch-yellow-ink) !important; border-color: var(--ch-yellow-deep) !important; font-weight: 800 !important;">{{ $page }}</span>
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center px-3.5 py-2 -ml-px text-sm font-bold text-gray-700 bg-white border border-gray-300 leading-5 hover:bg-gray-100 transition ease-in-out duration-150" style="font-weight: 800 !important;" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-3 py-2 -ml-px text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-r-md leading-5 hover:bg-gray-100 transition ease-in-out duration-150" style="font-weight: 800 !important;" aria-label="{{ __('pagination.next') }}">&rsaquo;</a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <span class="inline-flex items-center px-3 py-2 -ml-px text-sm font-bold text-gray-400 bg-gray-100 border border-gray-300 rounded-r-md leading-5 cursor-not-allowed" style="font-weight: 800 !important;" aria-hidden="true">&rsaquo;</span>
                </span>
            @endif
        </div>
    </nav>
@endif
