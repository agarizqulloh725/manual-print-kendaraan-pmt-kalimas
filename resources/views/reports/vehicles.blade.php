<x-layouts.app title="Rekap Kendaraan">
    <x-report-tabs />

    <x-filter-bar :action="route('reports.vehicles')" :search-value="$filters['search']"
                  search-placeholder="Cari plat / no tiket / barcode..."
                  :chips="$chips"
                  :export-url="route('reports.export', array_filter($filters))">
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

        <x-slot:summary>
            <x-report-totals :totals="$totals" />
        </x-slot:summary>

        <x-filter-ptosr-class :filters="$filters" :vehicle-classes="$vehicleClasses" />
    </x-filter-bar>

    <div class="flex flex-col gap-3">
        @forelse ($tickets as $ticket)
            <x-vehicle-item :ticket="$ticket" />
        @empty
            <p class="rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500">Tidak ada kendaraan yang cocok.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $tickets->onEachSide(1)->links('pagination.compact') }}
    </div>
</x-layouts.app>
