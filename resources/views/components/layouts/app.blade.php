@props(['title' => 'Cetak Timbangan Manual RORO'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-200 font-sans text-slate-800 antialiased">
    <div class="mx-auto max-w-3xl px-3 py-4 sm:py-8">
        <div class="overflow-hidden rounded-2xl bg-white shadow-xl">
            <header class="bg-slate-800 px-6 py-6 text-center text-white">
                <h1 class="text-xl font-bold tracking-wide sm:text-2xl">CETAK TIMBANGAN MANUAL RORO</h1>
                <p class="mt-1 text-sm text-slate-300">Pelabuhan Tanjung Perak Surabaya</p>
                @auth
                    <div class="mt-4 flex items-center justify-center gap-3 text-xs text-slate-300">
                        <span>👤 {{ auth()->user()->name }} · {{ auth()->user()->phone }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded bg-slate-700 px-2 py-1 font-semibold text-white hover:bg-slate-600">Keluar</button>
                        </form>
                    </div>
                @endauth
            </header>

            @auth
                <nav class="grid grid-cols-3 border-b border-slate-200 bg-slate-50 text-sm font-bold sm:text-base">
                    @foreach ([
                        ['route' => 'tickets.create', 'active' => ['tickets.create'], 'icon' => '✍️', 'label' => 'INPUT'],
                        ['route' => 'tickets.index', 'active' => ['tickets.index'], 'icon' => '🖨️', 'label' => 'REPRINT'],
                        ['route' => 'reports.vessels', 'active' => ['reports.*', 'tickets.show'], 'icon' => '📊', 'label' => 'REKAP'],
                    ] as $tab)
                        @php($isActive = request()->routeIs(...$tab['active']))
                        <a href="{{ route($tab['route']) }}"
                           @class([
                               'flex items-center justify-center gap-2 border-b-4 py-4 transition',
                               'border-sky-600 bg-white text-sky-700' => $isActive,
                               'border-transparent text-slate-600 hover:bg-white' => ! $isActive,
                           ])>
                            <span>{{ $tab['icon'] }}</span> {{ $tab['label'] }}
                        </a>
                    @endforeach
                </nav>
            @endauth

            <main class="p-5 sm:p-8">
                @if (session('status'))
                    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
