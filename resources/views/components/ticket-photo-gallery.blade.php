@props(['ticket'])

{{-- Read-only view of the photos already stored for a ticket; each opens full-screen (lightbox.js). --}}
@php
    $photos = array_filter([
        'Foto Kendaraan' => $ticket->photoUrl('vehicle'),
        'Foto Tiket' => $ticket->photoUrl('ticket'),
        'Barcode' => $ticket->photoUrl('barcode'),
    ]);
@endphp

@if ($photos)
    <div class="flex flex-col gap-3">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($photos as $label => $url)
                <figure class="flex flex-col gap-1">
                    <a href="{{ $url }}" data-lightbox="{{ $label }} · {{ $ticket->plate_number }}"
                       class="group relative flex aspect-video items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none">
                        <img src="{{ $url }}" alt="{{ $label }}" loading="lazy"
                             @class(['size-full transition group-hover:scale-105', 'object-contain p-2' => $label === 'Barcode', 'object-cover' => $label !== 'Barcode'])>
                        <span class="absolute right-1.5 bottom-1.5 rounded bg-slate-900/70 px-1.5 py-0.5 text-[10px] font-semibold text-white">🔍 Perbesar</span>
                    </a>
                    <figcaption class="text-xs font-semibold text-slate-600">{{ $label }}</figcaption>
                </figure>
            @endforeach
        </div>

        @if ($ticket->ticket_photo_path)
            <p class="font-mono text-xs break-all text-slate-600">
                ▮ Nilai barcode: {{ $ticket->barcode_value ?? '-' }}
                @if ($ticket->barcode_format)
                    <span class="rounded bg-slate-200 px-1.5 py-0.5 text-[10px]">{{ $ticket->barcode_format }}</span>
                @endif
            </p>
        @endif
    </div>
@endif
