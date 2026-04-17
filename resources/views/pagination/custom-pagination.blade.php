@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:justify-end">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-200 border border-gray-300 cursor-default rounded-lg">
                    {!! __('Previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-300">
                    {!! __('Previous') !!}
                </a>
            @endif

            {{-- Pagination Elements --}}
            <div class="hidden sm:flex sm:items-center sm:justify-center mx-4">
                <span class="relative z-0 inline-flex shadow-sm rounded-md -space-x-px">
                    {{-- First Page Link --}}
                    @if ($paginator->currentPage() > 3)
                        <a href="{{ $paginator->url(1) }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-300">1</a>
                        @if ($paginator->currentPage() > 4)
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 cursor-default">...</span>
                        @endif
                    @endif

                    {{-- Five-Page Carousel --}}
                    @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page">
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-gray-300 border border-gray-300 rounded-lg cursor-default">{{ $page }}</span>
                            </span>
                        @else
                            <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-300">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Last Page Link --}}
                    @if ($paginator->currentPage() < $paginator->lastPage() - 2)
                        @if ($paginator->currentPage() < $paginator->lastPage() - 3)
                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 cursor-default">...</span>
                        @endif
                        <a href="{{ $paginator->url($paginator->lastPage()) }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-300">{{ $paginator->lastPage() }}</a>
                    @endif
                </span>
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="ml-3 relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-300">
                    {!! __('Next') !!}
                </a>
            @else
                <span class="ml-3 relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-gray-200 border border-gray-300 cursor-default rounded-lg">
                    {!! __('Next') !!}
                </span>
            @endif
        </div>
    </nav>
@endif
