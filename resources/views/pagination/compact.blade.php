{{-- Compact pagination: prev / page numbers / next on desktop, prev / "x of y" / next on mobile. --}}
@if ($paginator->hasPages())
    @php
        $buttonClass = 'flex h-10 min-w-10 items-center justify-center rounded-lg border px-3 text-sm font-bold';
        $idleClass = $buttonClass.' border-slate-300 bg-white text-slate-700 hover:bg-slate-50';
        $disabledClass = $buttonClass.' cursor-not-allowed border-slate-200 bg-slate-50 text-slate-300';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-xs text-slate-500">
            Menampilkan <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold text-slate-700">{{ number_format($paginator->total(), 0, ',', '.') }}</span>
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="{{ $disabledClass }}" aria-disabled="true" aria-label="Sebelumnya">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idleClass }}" aria-label="Sebelumnya">‹</a>
            @endif

            <span class="px-3 text-sm font-semibold text-slate-600 sm:hidden">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1 text-slate-400">{{ $element }}</span>
                    @else
                        @foreach ($element as $page => $url)
                            @if ($page === $paginator->currentPage())
                                <span aria-current="page" class="{{ $buttonClass }} border-sky-600 bg-sky-600 text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $idleClass }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idleClass }}" aria-label="Berikutnya">›</a>
            @else
                <span class="{{ $disabledClass }}" aria-disabled="true" aria-label="Berikutnya">›</span>
            @endif
        </div>
    </nav>
@endif
