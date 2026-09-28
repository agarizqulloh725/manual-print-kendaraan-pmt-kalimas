<x-layouts.admin title="Tambah User">
    <a href="{{ route('admin.users.index') }}" class="mb-3 inline-block text-sm font-semibold text-sky-700 hover:underline">← Semua user</a>

    <form method="POST" action="{{ route('admin.users.store') }}" class="rounded-xl bg-white p-5 shadow-sm sm:p-6">
        @csrf
        <h2 class="mb-5 text-xl font-bold text-slate-800">Tambah User</h2>

        @include('admin.users._form')

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-slate-100 px-4 py-3 font-bold text-slate-700 hover:bg-slate-200">Batal</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-layouts.admin>
