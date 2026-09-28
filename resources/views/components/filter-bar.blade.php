@props([
    'action',
    'searchName' => 'search',
    'searchValue' => null,
    'searchPlaceholder' => 'Cari...',
    'chips' => [],
    'exportUrl' => null,
])

{{--
    Order: active filter chips, optional "summary" slot (e.g. totals), then the search / filter / export row.
    Search stays visible; every other filter lives in a popover panel (bottom sheet on mobile, dialog on desktop).
    Each chip: ['label' => string, 'remove' => list of query keys | null when it is a non-removable default].
--}}
@php
    $activeCount = collect($chips)->filter(fn (array $chip) => $chip['remove'] !== null)->count();
    $resetKeys = collect($chips)->pluck('remove')->filter()->flatten()->push('page')->all();
@endphp

<form method="GET" action="{{ $action }}" class="mb-5">
    @if ($chips)
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach ($chips as $chip)
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 py-1 pr-1 pl-3 text-xs font-semibold text-slate-700">
                    {{ $chip['label'] }}
                    @if ($chip['remove'])
                        <a href="{{ request()->fullUrlWithoutQuery([...$chip['remove'], 'page']) }}" aria-label="Hapus filter {{ $chip['label'] }}"
                           class="flex size-5 items-center justify-center rounded-full text-slate-500 hover:bg-slate-300 hover:text-slate-800">×</a>
                    @else
                        <span class="w-1"></span>
                    @endif
                </span>
            @endforeach
            @if ($activeCount > 1)
                <a href="{{ request()->fullUrlWithoutQuery($resetKeys) }}" class="text-xs font-semibold text-sky-700 hover:underline">Reset semua</a>
            @endif
        </div>
    @endif

    {{ $summary ?? '' }}

    <div class="flex gap-2">
        <div class="relative flex-1">
            <svg class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
            </svg>
            <input type="search" name="{{ $searchName }}" value="{{ $searchValue }}" placeholder="{{ $searchPlaceholder }}"
                   aria-label="{{ $searchPlaceholder }}" class="form-input pl-10">
        </div>

        <button type="button" popovertarget="filter-panel" aria-label="Buka filter"
                @class([
                    'relative inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 text-sm font-bold transition',
                    'border-sky-500 bg-sky-50 text-sky-700' => $activeCount > 0,
                    'border-slate-300 bg-white text-slate-600 hover:bg-slate-50' => $activeCount === 0,
                ])>
            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M2.63 3.63A.75.75 0 0 1 3.25 3h13.5a.75.75 0 0 1 .59 1.21L12 11.04v4.71a.75.75 0 0 1-1.13.65l-2.5-1.5a.75.75 0 0 1-.37-.65v-3.21L2.66 4.21a.75.75 0 0 1-.03-.58Z" clip-rule="evenodd" />
            </svg>
            <span class="hidden sm:inline">Filter</span>
            @if ($activeCount > 0)
                <span class="absolute -top-2 -right-2 flex size-5 items-center justify-center rounded-full bg-sky-600 text-[11px] text-white">{{ $activeCount }}</span>
            @endif
        </button>

        @if ($exportUrl)
            <a href="{{ $exportUrl }}" title="Export CSV" aria-label="Export CSV"
               class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-sky-600 px-3 text-sm font-bold text-white hover:bg-sky-700">
                <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.59L6.28 8.37a.75.75 0 0 0-1.06 1.06l4.25 4.25a.75.75 0 0 0 1.06 0l4.25-4.25a.75.75 0 1 0-1.06-1.06l-2.97 2.97V2.75Z" />
                    <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                </svg>
                <span class="hidden sm:inline">Export</span>
            </a>
        @endif
    </div>

    <div id="filter-panel" popover
         class="inset-x-0 top-auto bottom-0 m-0 max-h-[85vh] w-full max-w-none overflow-y-auto rounded-t-2xl border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 sm:inset-0 sm:m-auto sm:h-fit sm:max-w-md sm:rounded-2xl">
        <div class="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-5 py-4">
            <h2 class="text-base font-bold text-slate-800">Filter</h2>
            <button type="button" popovertarget="filter-panel" popovertargetaction="hide" aria-label="Tutup filter"
                    class="flex size-8 items-center justify-center rounded-full text-xl text-slate-500 hover:bg-slate-100">×</button>
        </div>

        <div class="flex flex-col gap-4 px-5 py-4">
            {{ $slot }}
        </div>

        <div class="sticky bottom-0 flex gap-3 border-t border-slate-100 bg-white px-5 py-4">
            <a href="{{ request()->fullUrlWithoutQuery($resetKeys) }}" class="flex-1 rounded-lg bg-slate-100 px-4 py-3 text-center font-bold text-slate-700 hover:bg-slate-200">Reset</a>
            <button type="submit" class="btn-primary flex-1">Terapkan</button>
        </div>
    </div>
</form>
