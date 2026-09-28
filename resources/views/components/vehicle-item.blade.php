@props(['ticket', 'showVessel' => true])

<a href="{{ route('tickets.show', $ticket) }}"
   class="flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-sky-400 hover:bg-sky-50 sm:p-4">
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-base font-bold text-slate-800">{{ $ticket->plate_number }}</span>
            <x-ptosr-badge :verified="$ticket->isPtosrVerified()" />
        </div>
        <p class="mt-0.5 text-xs text-slate-500">
            {{ $ticket->created_at->format('d/m/Y H:i') }} · {{ $ticket->ticket_number }}
        </p>
        <p class="mt-1 text-sm text-slate-700">
            Gol. {{ $ticket->vehicle_class->value }} · {{ number_format($ticket->weight_kg, 0, ',', '.') }} Kg
            @if ($showVessel)
                <span class="text-slate-500">· 🚢 {{ $ticket->vessel_name }} → {{ $ticket->destination_port_name }}</span>
            @endif
        </p>
        @if ($ticket->barcode_value)
            <p class="truncate font-mono text-xs text-slate-500">▮ {{ $ticket->barcode_value }}</p>
        @endif
    </div>
    <span class="text-xl text-slate-400" aria-hidden="true">›</span>
</a>
