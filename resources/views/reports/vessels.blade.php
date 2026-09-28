<x-layouts.app title="Rekap per Kapal">
    <x-report-tabs />

    <x-filter-bar :action="route('reports.vessels')" search-name="vessel" :search-value="$filters['vessel']"
                  search-placeholder="Cari kapal / voyage / tujuan..."
                  :chips="$chips"
                  :export-url="route('reports.export', array_filter($filters))">
        <x-filter-date-range :filters="$filters" />
    </x-filter-bar>

    <div class="flex flex-col gap-3">
        @forelse ($vessels as $vessel)
            @php($nonPtosr = $vessel->total_vehicles - $vessel->ptosr_vehicles)
            <a href="{{ route('reports.vessel', $vessel->voyage_no) }}"
               class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-sky-400 hover:bg-sky-50 sm:p-4">
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-slate-800">🚢 {{ $vessel->vessel_name }} <span class="font-normal text-slate-500">→ {{ $vessel->destination_port_name }}</span></p>
                    <p class="truncate text-xs text-slate-500">{{ $vessel->voyage_no }} · input terakhir {{ \Illuminate\Support\Carbon::parse($vessel->last_input_at)->format('d/m/Y H:i') }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700">{{ number_format($vessel->total_vehicles, 0, ',', '.') }} kendaraan</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700">{{ \App\Models\Ticket::formatTon($vessel->total_weight_ton) }}</span>
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-bold text-emerald-700">✔ {{ $vessel->ptosr_vehicles }} PTOSR</span>
                        @if ($nonPtosr > 0)
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 font-bold text-amber-700">⚠ {{ $nonPtosr }} NON PTOSR</span>
                        @endif
                    </div>
                </div>
                <span class="text-xl text-slate-400" aria-hidden="true">›</span>
            </a>
        @empty
            <p class="rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada kapal pada periode ini.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $vessels->onEachSide(1)->links('pagination.compact') }}
    </div>
</x-layouts.app>
