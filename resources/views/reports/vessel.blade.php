<x-layouts.app :title="'Rekap '.$vessel->vessel_name">
    <x-report-tabs />

    <a href="{{ route('reports.vessels') }}" class="mb-3 inline-block text-sm font-semibold text-sky-700 hover:underline">← Semua kapal</a>

    <div class="mb-5 rounded-xl bg-slate-800 p-4 text-white">
        <p class="text-lg font-bold">🚢 {{ $vessel->vessel_name }}</p>
        <p class="text-sm text-slate-300">Tujuan {{ $vessel->destination_port_name }} · Dermaga {{ $vessel->berth_name ?? '-' }}</p>
        <p class="mt-1 text-xs break-all text-slate-400">{{ $vessel->voyage_no }} · {{ $vessel->operator_name }}</p>
    </div>

    <x-filter-bar :action="route('reports.vessel', $vessel->voyage_no)" :search-value="$filters['search']"
                  search-placeholder="Cari plat / no tiket / barcode..."
                  :chips="$chips"
                  :export-url="route('reports.export', array_filter($filters))">
        <x-slot:summary>
            <x-report-totals :totals="$totals" show-weight />
            <h2 class="mb-3 text-sm font-bold tracking-wide text-slate-600 uppercase">Daftar Kendaraan</h2>
        </x-slot:summary>

        <x-filter-ptosr-class :filters="$filters" :vehicle-classes="$vehicleClasses" />
    </x-filter-bar>

    <div class="flex flex-col gap-3">
        @forelse ($tickets as $ticket)
            <x-vehicle-item :ticket="$ticket" :show-vessel="false" />
        @empty
            <p class="rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500">Tidak ada kendaraan yang cocok.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $tickets->onEachSide(1)->links('pagination.compact') }}
    </div>
</x-layouts.app>
