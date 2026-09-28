@props(['title' => 'Admin'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Admin Timbangan RORO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
    <header class="bg-slate-900 text-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4">
            <div>
                <p class="text-xs font-semibold tracking-widest text-sky-300 uppercase">Administrator</p>
                <h1 class="text-lg font-bold">Timbangan Manual RORO</h1>
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-300">
                <a href="{{ route('tickets.create') }}" class="rounded bg-slate-700 px-2 py-1 font-semibold text-white hover:bg-slate-600">↗ Aplikasi Operator</a>
                <span class="hidden sm:inline">👤 {{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded bg-slate-700 px-2 py-1 font-semibold text-white hover:bg-slate-600">Keluar</button>
                </form>
            </div>
        </div>
        <nav class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 text-sm font-bold">
            @foreach ([
                ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'label' => '📈 Monitoring'],
                ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'label' => '👥 User'],
                ['route' => 'admin.tickets.index', 'active' => 'admin.tickets.*', 'label' => '🎫 Tiket'],
            ] as $tab)
                @php($isActive = request()->routeIs($tab['active']))
                <a href="{{ route($tab['route']) }}"
                   @class([
                       'rounded-t-lg px-4 py-3 whitespace-nowrap transition',
                       'bg-slate-100 text-slate-900' => $isActive,
                       'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $isActive,
                   ])>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
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
</body>
</html>
