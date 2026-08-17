{{--
    =========================================================
    [LOCKED] MOBILE PAGINATION - DO NOT MODIFY LAYOUT
    =========================================================
    This mobile pagination has been perfectly tuned to match the desktop
    Tailwind styling while being horizontally scrollable on mobile.
    - DO NOT change the display properties or wrappers.
    - DO NOT remove the overflow-x scroll container.
    - DO NOT change the font-weight: 800 inline styles.
    (Locked as per user request to prevent layout breaks)
--}}
@if ($paginator->hasPages())
    <div style="text-align: center; margin-top: 12px; margin-bottom: 24px;">
        <div style="margin-bottom: 12px; font-size: 14px; color: #475569;">
            Showing <strong style="font-weight: 800 !important; color: #1e293b !important;">{{ $paginator->firstItem() }}</strong> to <strong style="font-weight: 800 !important; color: #1e293b !important;">{{ $paginator->lastItem() }}</strong> of <strong style="font-weight: 800 !important; color: #1e293b !important;">{{ $paginator->total() }}</strong> results
        </div>
        
        <div style="width: 100%; overflow-x: auto; padding-bottom: 8px;">
            <span class="relative z-0 inline-flex shadow-sm rounded-md">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <span class="relative inline-flex items-center px-4 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 cursor-default rounded-l-md leading-5" style="font-weight: 800 !important;" aria-hidden="true">&lsaquo; Previous</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-l-md leading-5 hover:bg-gray-100 transition ease-in-out duration-150" style="font-weight: 800 !important;" aria-label="{{ __('pagination.previous') }}">&lsaquo; Previous</a>
                @endif



                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm text-gray-700 bg-white border border-gray-300 rounded-r-md leading-5 hover:bg-gray-100 transition ease-in-out duration-150" style="font-weight: 800 !important;" aria-label="{{ __('pagination.next') }}">Next &rsaquo;</a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm text-gray-400 bg-gray-100 border border-gray-300 cursor-default rounded-r-md leading-5" style="font-weight: 800 !important;" aria-hidden="true">Next &rsaquo;</span>
                    </span>
                @endif
            </span>
        </div>
    </div>
@endif
