<x-layouts.admin title="Monitoring">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Monitoring</h2>
            <p class="text-sm text-slate-500">{{ now()->format('d/m/Y') }} · diperbarui {{ now()->format('H:i') }}</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">↻ Muat ulang</a>
    </div>

    {{-- Headline numbers --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Tiket hari ini', 'value' => $stats['tickets_today'], 'hint' => "{$stats['operators_today']} operator aktif", 'class' => 'text-slate-900'],
            ['label' => 'PTOSR hari ini', 'value' => $stats['ptosr_today'], 'hint' => ($stats['tickets_today'] - $stats['ptosr_today']).' belum diverifikasi', 'class' => 'text-emerald-700'],
            ['label' => 'NON PTOSR (semua)', 'value' => $stats['non_ptosr_backlog'], 'hint' => 'Perlu diinput di PTOSR', 'class' => 'text-amber-700'],
            ['label' => 'User aktif', 'value' => $stats['active_users'], 'hint' => "{$stats['inactive_users']} nonaktif", 'class' => 'text-slate-900'],
        ] as $stat)
            <div class="rounded-xl bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase">{{ $stat['label'] }}</p>
                <p class="mt-1 text-3xl font-bold {{ $stat['class'] }}">{{ number_format($stat['value'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $stat['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        {{-- 7-day trend: single series, so no legend; the title names it and each bar has a hover tooltip. --}}
        <section class="rounded-xl bg-white p-4 shadow-sm lg:col-span-2">
            <h3 class="text-sm font-bold text-slate-700">Tiket per hari · 7 hari terakhir</h3>
            @php($maxTotal = max(1, collect($trend)->max('total')))
            <div class="mt-4 flex h-44 items-end gap-2 border-b border-slate-200" role="img"
                 aria-label="Jumlah tiket per hari selama 7 hari terakhir">
                @foreach ($trend as $day)
                    <div class="group relative flex h-full flex-1 flex-col items-center justify-end"
                         title="{{ $day['date']->format('d/m/Y') }}: {{ $day['total'] }} tiket ({{ $day['ptosr'] }} PTOSR)">
                        <span class="mb-1 text-xs font-semibold text-slate-600">{{ $day['total'] ?: '' }}</span>
                        <div @class([
                                'w-full max-w-12 rounded-t transition group-hover:opacity-80',
                                'bg-sky-600' => $day['date']->isToday(),
                                'bg-sky-300' => ! $day['date']->isToday(),
                             ])
                             style="height: {{ $day['total'] > 0 ? max(2, round($day['total'] / $maxTotal * 100)) : 0 }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex gap-2">
                @foreach ($trend as $day)
                    <span @class(['flex-1 text-center text-xs', 'font-bold text-slate-800' => $day['date']->isToday(), 'text-slate-500' => ! $day['date']->isToday()])>
                        {{ $day['date']->isToday() ? 'Hari ini' : $day['date']->format('d/m') }}
                    </span>
                @endforeach
            </div>
        </section>

        {{-- PTOSR schedule API health --}}
        <section class="rounded-xl bg-white p-4 shadow-sm">
            <h3 class="text-sm font-bold text-slate-700">Status API Jadwal Kapal (PTOS-R)</h3>
            @if ($apiStatus)
                @php($checkedAt = \Illuminate\Support\Carbon::parse($apiStatus['checked_at']))
                <div @class([
                    'mt-4 rounded-lg p-3',
                    'bg-emerald-50 text-emerald-800' => $apiStatus['ok'],
                    'bg-red-50 text-red-800' => ! $apiStatus['ok'],
                ])>
                    <p class="font-bold">{{ $apiStatus['ok'] ? '✔ Terhubung' : '✘ Gagal' }}</p>
                    <p class="text-xs">
                        {{ $apiStatus['ok'] ? $apiStatus['vessels'].' kapal diterima' : 'Error: '.$apiStatus['error'] }}
                    </p>
                </div>
                <p class="mt-2 text-xs text-slate-500">Terakhir dicek {{ $checkedAt->format('d/m/Y H:i') }}</p>
            @else
                <p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-500">Belum ada pengecekan. Status muncul setelah operator membuka form INPUT.</p>
            @endif
        </section>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-bold text-slate-700">Aktivitas operator hari ini</h3>
            <div class="flex flex-col divide-y divide-slate-100">
                @forelse ($operatorActivity as $activity)
                    <div class="flex items-center justify-between gap-3 py-2 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-800">{{ $activity->user?->name ?? '-' }}</p>
                            <p class="text-xs text-slate-500">Input terakhir {{ \Illuminate\Support\Carbon::parse($activity->last_input_at)->format('H:i') }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $activity->total }} tiket</span>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-slate-500">Belum ada input hari ini.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-bold text-slate-700">Kapal hari ini</h3>
            <div class="flex flex-col divide-y divide-slate-100">
                @forelse ($vesselsToday as $vessel)
                    <a href="{{ route('reports.vessel', $vessel->voyage_no) }}" class="flex items-center justify-between gap-3 py-2 text-sm hover:bg-slate-50">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-800">🚢 {{ $vessel->vessel_name }} <span class="font-normal text-slate-500">→ {{ $vessel->destination_port_name }}</span></p>
                            <p class="text-xs text-slate-500">{{ \App\Models\Ticket::formatTon($vessel->total_weight_ton) }}</p>
                        </div>
                        <div class="flex shrink-0 gap-1 text-xs font-bold">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">{{ $vessel->total }}</span>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700">✔ {{ $vessel->ptosr }}</span>
                            @if ($vessel->total - $vessel->ptosr > 0)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-amber-700">⚠ {{ $vessel->total - $vessel->ptosr }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="py-4 text-center text-sm text-slate-500">Belum ada kapal hari ini.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-xl bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-700">Tiket terbaru</h3>
            <a href="{{ route('admin.tickets.index') }}" class="text-xs font-semibold text-sky-700 hover:underline">Kelola tiket →</a>
        </div>
        <div class="flex flex-col gap-2">
            @forelse ($recentTickets as $ticket)
                <x-vehicle-item :ticket="$ticket" />
            @empty
                <p class="py-4 text-center text-sm text-slate-500">Belum ada tiket.</p>
            @endforelse
        </div>
    </section>
</x-layouts.admin>
