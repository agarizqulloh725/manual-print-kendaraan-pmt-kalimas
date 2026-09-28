@props(['totals', 'showWeight' => false])

{{-- Total weight is only meaningful per vessel, so it is shown on the vessel detail page only. --}}
@php
    $stats = [
        ['label' => 'Kendaraan', 'value' => $totals['vehicles'], 'class' => 'text-slate-800'],
        ...($showWeight ? [['label' => 'Total Berat (Kg)', 'value' => $totals['weight_kg'], 'class' => 'text-slate-800']] : []),
        ['label' => 'PTOSR', 'value' => $totals['ptosr'], 'class' => 'text-emerald-700'],
        ['label' => 'NON PTOSR', 'value' => $totals['vehicles'] - $totals['ptosr'], 'class' => 'text-amber-700'],
    ];
@endphp

<div @class(['mb-6 grid gap-3', 'grid-cols-2 sm:grid-cols-4' => $showWeight, 'grid-cols-3' => ! $showWeight])>
    @foreach ($stats as $stat)
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs font-semibold text-slate-500 uppercase">{{ $stat['label'] }}</p>
            <p class="mt-1 text-xl font-bold {{ $stat['class'] }}">{{ number_format($stat['value'], 0, ',', '.') }}</p>
        </div>
    @endforeach
</div>
