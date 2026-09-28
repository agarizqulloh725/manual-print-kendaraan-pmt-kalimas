<div class="mb-6 grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1 text-sm font-bold">
    @foreach ([
        ['route' => 'reports.vessels', 'active' => ['reports.vessels', 'reports.vessel'], 'label' => '🚢 Kapal'],
        ['route' => 'reports.vehicles', 'active' => ['reports.vehicles'], 'label' => '🚚 Kendaraan'],
    ] as $tab)
        @php($isActive = request()->routeIs(...$tab['active']))
        <a href="{{ route($tab['route']) }}"
           @class([
               'rounded-lg px-3 py-2 text-center transition',
               'bg-white text-sky-700 shadow' => $isActive,
               'text-slate-600 hover:bg-white/60' => ! $isActive,
           ])>
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
