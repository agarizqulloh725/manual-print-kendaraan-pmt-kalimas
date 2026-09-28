<x-layouts.admin :title="'Edit '.$user->name">
    <a href="{{ route('admin.users.index') }}" class="mb-3 inline-block text-sm font-semibold text-sky-700 hover:underline">← Semua user</a>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="rounded-xl bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')
        <div class="mb-5">
            <h2 class="text-xl font-bold text-slate-800">Edit User</h2>
            <p class="text-sm text-slate-500">
                {{ number_format($user->tickets_count, 0, ',', '.') }} tiket ·
                login terakhir {{ $user->last_login_at?->format('d/m/Y H:i') ?? '-' }}
            </p>
        </div>

        @include('admin.users._form')

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-slate-100 px-4 py-3 font-bold text-slate-700 hover:bg-slate-200">Batal</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</x-layouts.admin>
