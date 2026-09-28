@props(['verified'])

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold tracking-wide whitespace-nowrap',
    'bg-emerald-100 text-emerald-700' => $verified,
    'bg-amber-100 text-amber-700' => ! $verified,
]) }}>
    {{ $verified ? '✔ PTOSR' : '⚠ NON PTOSR' }}
</span>
