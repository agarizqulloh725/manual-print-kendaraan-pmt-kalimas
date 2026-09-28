<x-layouts.app title="Reprint Tiket">
    <x-filter-bar :action="route('tickets.index')" :search-value="$filters['search']"
                  search-placeholder="Cari plat / no tiket / barcode..."
                  :chips="$chips">
        <x-slot:summary>
            <p class="mb-3 text-sm text-slate-500">
                <span class="font-bold text-slate-800">{{ number_format($tickets->total(), 0, ',', '.') }}</span> tiket ditemukan
            </p>
        </x-slot:summary>

        <x-filter-date-range :filters="$filters" />

        <div>
            <label for="voyage_no" class="form-label">Kapal</label>
            <select id="voyage_no" name="voyage_no" class="form-input">
                <option value="">Semua kapal</option>
                @foreach ($voyages as $voyage)
                    <option value="{{ $voyage->voyage_no }}" @selected($filters['voyage_no'] === $voyage->voyage_no)>{{ $voyage->vessel_name }}</option>
                @endforeach
            </select>
        </div>

        <x-filter-ptosr-class :filters="$filters" :vehicle-classes="$vehicleClasses" />
    </x-filter-bar>

    <div class="flex flex-col gap-4">
        @forelse ($tickets as $ticket)
            @php
                $photoCount = collect([$ticket->vehicle_photo_path, $ticket->ticket_photo_path, $ticket->barcode_path])->filter()->count();
                $togglerId = 'photos_toggle_'.$ticket->id;
            @endphp
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="flex min-w-0 flex-col gap-0.5 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('tickets.show', $ticket) }}" class="truncate text-lg font-bold text-slate-800 hover:text-sky-700 hover:underline">{{ $ticket->plate_number }}</a>
                        <x-ptosr-badge :verified="$ticket->isPtosrVerified()" />
                    </div>
                    <p class="truncate text-xs text-slate-500">{{ $ticket->ticket_number }} · {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                    <p class="mt-1 truncate text-sm text-slate-700">🚢 {{ $ticket->vessel_name }} → {{ $ticket->destination_port_name }}</p>
                    <p class="truncate text-sm text-slate-600">
                        Gol. {{ $ticket->vehicle_class->value }} · {{ number_format($ticket->weight_kg, 0, ',', '.') }} Kg ({{ $ticket->weight_mode->label() }})
                    </p>
                    <p class="truncate font-mono text-xs text-slate-600">▮ {{ $ticket->barcode_value ?? '-' }}</p>
                    <p class="truncate text-xs text-slate-500">Petugas: {{ $ticket->user?->name }} · Dicetak {{ $ticket->print_count }}x</p>
                </div>

                {{-- CSS-only toggle: the checkbox (peer) opens the photo panel below the fixed action bar. --}}
                <input type="checkbox" id="{{ $togglerId }}" class="peer sr-only" @checked($errors->any() && old('ticket_id') == $ticket->id)>

                <div class="grid grid-cols-2 gap-2 border-t border-slate-100 bg-slate-50 p-3 peer-checked:[&_[data-chevron]]:rotate-180 peer-focus-visible:[&_label]:ring-2 peer-focus-visible:[&_label]:ring-sky-300">
                    <label for="{{ $togglerId }}"
                           class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 select-none hover:bg-slate-100">
                        📷 Foto <span class="font-normal text-slate-500">{{ $photoCount }}/3</span>
                        <span class="text-xs text-slate-400 transition" data-chevron>▼</span>
                    </label>
                    <form method="POST" action="{{ route('tickets.reprint', $ticket) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-sky-600 px-3 py-2 text-sm font-bold text-white hover:bg-sky-700">🖨️ Cetak Ulang</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('tickets.photos', $ticket) }}" enctype="multipart/form-data"
                      class="hidden flex-col gap-3 border-t border-slate-100 p-4 peer-checked:flex">
                    @csrf
                    <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                    <x-photo-fields :id-suffix="'_'.$ticket->id"
                                    :vehicle-photo-url="$ticket->vehiclePhotoUrl()"
                                    :ticket-photo-url="$ticket->ticketPhotoUrl()"
                                    :barcode-url="$ticket->barcodeUrl()"
                                    :barcode-value="$ticket->barcode_value"
                                    :barcode-format="$ticket->barcode_format" />
                    <button type="submit" class="btn-secondary">Simpan Foto &amp; Barcode</button>
                </form>
            </div>
        @empty
            <p class="rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500">Tidak ada tiket yang cocok dengan filter.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $tickets->onEachSide(1)->links('pagination.compact') }}
    </div>
</x-layouts.app>
