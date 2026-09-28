@props(['totals', 'showWeight' => false])

{{-- Total weight is only meaningful per vessel, so it is shown on the vessel detail page only. --}}
@php
    $count = fn (int $value): string => number_format($value, 0, ',', '.');

    $stats = [
        ['label' => 'Kendaraan', 'value' => $count($totals['vehicles']), 'class' => 'text-slate-800'],
        ...($showWeight ? [['label' => 'Total Tonase', 'value' => \App\Models\Ticket::formatTon($totals['weight_ton']), 'class' => 'text-slate-800']] : []),
        ['label' => 'PTOSR', 'value' => $count($totals['ptosr']), 'class' => 'text-emerald-700'],
        ['label' => 'NON PTOSR', 'value' => $count($totals['vehicles'] - $totals['ptosr']), 'class' => 'text-amber-700'],
    ];
@endphp

<div @class(['mb-6 grid gap-3', 'grid-cols-2 sm:grid-cols-4' => $showWeight, 'grid-cols-3' => ! $showWeight])>
    @foreach ($stats as $stat)
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs font-semibold text-slate-500 uppercase">{{ $stat['label'] }}</p>
            <p class="mt-1 text-xl font-bold {{ $stat['class'] }}">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>
