<x-layouts.admin title="User">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-bold text-slate-800">Manajemen User</h2>
        <a href="{{ route('admin.users.create') }}" class="btn-primary px-4 py-2 text-sm">＋ Tambah User</a>
    </div>

    <x-filter-bar :action="route('admin.users.index')" :search-value="$filters['search']"
                  search-placeholder="Cari nama / nomor HP..." :chips="$chips">
        <div>
            <span class="form-label">Peran</span>
            <div class="grid grid-cols-3 gap-2 text-sm font-semibold">
                @foreach (['' => 'Semua', ...collect($roles)->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all()] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $value }}" class="peer sr-only" @checked(($filters['role'] ?? '') === (string) $value)>
                        <span class="block truncate rounded-lg border border-slate-300 px-2 py-2 text-center text-slate-600 peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:text-sky-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <span class="form-label">Status</span>
            <div class="grid grid-cols-3 gap-2 text-sm font-semibold">
                @foreach (['' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="status" value="{{ $value }}" class="peer sr-only" @checked(($filters['status'] ?? '') === (string) $value)>
                        <span class="block rounded-lg border border-slate-300 px-2 py-2 text-center text-slate-600 peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:text-sky-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </x-filter-bar>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="hidden grid-cols-[2fr_1.2fr_1fr_1fr_1fr_auto] gap-3 border-b border-slate-100 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-500 uppercase md:grid">
            <span>Nama</span><span>Nomor HP</span><span>Peran</span><span>Tiket</span><span>Login terakhir</span><span class="w-36 text-right">Aksi</span>
        </div>
        @forelse ($users as $user)
            <div @class(['grid items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm last:border-0 md:grid-cols-[2fr_1.2fr_1fr_1fr_1fr_auto] md:gap-3', 'opacity-60' => ! $user->is_active])>
                <div class="min-w-0">
                    <p class="truncate font-bold text-slate-800">
                        {{ $user->name }}
                        @if ($user->is(auth()->user()))
                            <span class="text-xs font-normal text-slate-500">(Anda)</span>
                        @endif
                    </p>
                    @unless ($user->is_active)
                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-bold text-red-700">Nonaktif</span>
                    @endunless
                </div>
                <span class="font-mono text-slate-700">{{ $user->phone }}</span>
                <span>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-bold',
                        'bg-sky-100 text-sky-700' => $user->isAdmin(),
                        'bg-slate-100 text-slate-700' => ! $user->isAdmin(),
                    ])>{{ $user->role->label() }}</span>
                </span>
                <span class="text-slate-600"><span class="md:hidden">Tiket: </span>{{ number_format($user->tickets_count, 0, ',', '.') }}</span>
                <span class="text-xs text-slate-500"><span class="md:hidden">Login: </span>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '-' }}</span>
                <div class="flex w-full gap-2 md:w-36 md:justify-end">
                    <a href="{{ route('admin.users.edit', $user) }}" class="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-50 md:flex-none">Edit</a>
                    @if (! $user->is(auth()->user()) && $user->tickets_count === 0)
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="flex-1 md:flex-none"
                              onsubmit="return confirm('Hapus akun {{ $user->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-lg border border-red-300 px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50">Hapus</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-6 text-center text-sm text-slate-500">Tidak ada user yang cocok.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $users->onEachSide(1)->links('pagination.compact') }}
    </div>
</x-layouts.admin>
