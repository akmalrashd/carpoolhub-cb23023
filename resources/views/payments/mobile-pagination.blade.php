{{-- Numbered mobile pagination: centered "Showing" text + page numbers, matching desktop. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" style="text-align: center; margin-top: 12px; margin-bottom: 24px;">
        <p class="text-sm leading-5" style="margin-bottom: 12px; font-size: 14px; color: #475569;">
            Showing
            <strong style="font-weight: 800 !important; color: #1e293b !important;">{{ $paginator->firstItem() }}</strong>
            to
            <strong style="font-weight: 800 !important; color: #1e293b !important;">{{ $paginator->lastItem() }}</strong>
            of
            <strong style="font-weight: 800 !important; color: #1e293b !important;">{{ $paginator->total() }}</strong>
            results
        </p>

        <div style="width: 100%; overflow-x: auto; padding-bottom: 8px; display: flex; justify-content: center;">
            <span class="relative z-0 inline-flex shadow-sm rounded-md">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <span class="relative inline-flex items-center justify-center px-2 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 cursor-default rounded-l-md leading-5" style="font-weight: 800 !important; min-width: 36px; height: 36px;" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center justify-center px-2 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-l-md leading-5 hover:bg-gray-100 transition ease-in-out duration-150" style="font-weight: 800 !important; min-width: 36px; height: 36px;" aria-label="{{ __('pagination.previous') }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="relative inline-flex items-center justify-center px-4 py-2 -ml-px text-sm text-gray-700 bg-white border border-gray-300 cursor-default leading-5" style="font-weight: 800 !important; min-width: 36px; height: 36px;">{{ $element }}</span>
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="relative inline-flex items-center justify-center px-4 py-2 -ml-px text-sm border cursor-default leading-5" style="font-weight: 800 !important; min-width: 36px; height: 36px; background: var(--ch-yellow); border-color: var(--ch-yellow-deep); color: var(--ch-yellow-ink);">{{ $page }}</span>
                                </span>
                            @else
                                <a href="{{ $url }}" class="relative inline-flex items-center justify-center px-4 py-2 -ml-px text-sm text-gray-700 bg-white border border-gray-300 leading-5 hover:bg-gray-100 transition ease-in-out duration-150" style="font-weight: 800 !important; min-width: 36px; height: 36px;" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center justify-center px-2 py-2 -ml-px text-sm text-gray-700 bg-white border border-gray-300 rounded-r-md leading-5 hover:bg-gray-100 transition ease-in-out duration-150" style="font-weight: 800 !important; min-width: 36px; height: 36px;" aria-label="{{ __('pagination.next') }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <span class="relative inline-flex items-center justify-center px-2 py-2 -ml-px text-sm text-gray-400 bg-gray-100 border border-gray-300 cursor-default rounded-r-md leading-5" style="font-weight: 800 !important; min-width: 36px; height: 36px;" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
