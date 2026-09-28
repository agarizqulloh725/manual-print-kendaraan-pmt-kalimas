<x-layouts.admin title="Tiket">
    <h2 class="mb-5 text-xl font-bold text-slate-800">Manajemen Tiket</h2>

    <x-filter-bar :action="route('admin.tickets.index')" :search-value="$filters['search']"
                  search-placeholder="Cari plat / no tiket / barcode..." :chips="$chips"
                  :export-url="route('reports.export', array_filter($filters))">
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

    <div class="overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="hidden grid-cols-[1.4fr_1.6fr_0.6fr_0.9fr_1fr_auto] gap-3 border-b border-slate-100 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-500 uppercase lg:grid">
            <span>Plat / Tiket</span><span>Kapal</span><span>Gol</span><span class="text-right">Berat</span><span>Petugas</span><span class="w-36 text-right">Aksi</span>
        </div>
        @forelse ($tickets as $ticket)
            <div class="grid items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm last:border-0 lg:grid-cols-[1.4fr_1.6fr_0.6fr_0.9fr_1fr_auto] lg:gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('tickets.show', $ticket) }}" class="truncate font-bold text-slate-800 hover:text-sky-700 hover:underline">{{ $ticket->plate_number }}</a>
                        <x-ptosr-badge :verified="$ticket->isPtosrVerified()" />
                    </div>
                    <p class="truncate text-xs text-slate-500">{{ $ticket->ticket_number }} · {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <p class="truncate text-slate-700">🚢 {{ $ticket->vessel_name }} <span class="text-slate-500">→ {{ $ticket->destination_port_name }}</span></p>
                <span class="text-slate-700"><span class="lg:hidden">Gol. </span>{{ $ticket->vehicle_class->value }}</span>
                <span class="text-slate-700 lg:text-right">{{ $ticket->tonnageLabel() }}</span>
                <span class="truncate text-xs text-slate-500">{{ $ticket->user?->name }}</span>
                <div class="flex w-full gap-2 lg:w-36 lg:justify-end">
                    <a href="{{ route('admin.tickets.edit', $ticket) }}" class="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-50 lg:flex-none">Edit</a>
                    <form method="POST" action="{{ route('admin.tickets.destroy', $ticket) }}" class="flex-1 lg:flex-none"
                          onsubmit="return confirm('Hapus tiket {{ $ticket->ticket_number }} ({{ $ticket->plate_number }})? Foto ikut terhapus.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full rounded-lg border border-red-300 px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="p-6 text-center text-sm text-slate-500">Tidak ada tiket yang cocok dengan filter.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $tickets->onEachSide(1)->links('pagination.compact') }}
    </div>
</x-layouts.admin>
