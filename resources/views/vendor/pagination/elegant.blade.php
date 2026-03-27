@if ($paginator->hasPages())
    <nav class="flex items-center space-x-1 text-sm select-none">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
               class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-100 text-gray-700 transition">
                ‹
            </a>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-3 py-1.5 text-gray-500">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-3 py-1.5 bg-indigo-500 text-white rounded-lg font-semibold shadow-sm">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-100 text-gray-700 transition">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
               class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-100 text-gray-700 transition">
                ›
            </a>
        @else
            <span class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">›</span>
        @endif
    </nav>
@endif
