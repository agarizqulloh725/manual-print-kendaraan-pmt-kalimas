<x-layouts.app title="Daftar Operator">
    <h2 class="mb-6 text-center text-lg font-bold text-slate-700">DAFTAR OPERATOR</h2>

    <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
        @csrf

        <div>
            <label for="name" class="form-label">Nama Lengkap</label>
            <input id="name" name="name" type="text" autocomplete="name" required autofocus
                   value="{{ old('name') }}" class="form-input">
        </div>

        <div>
            <label for="phone" class="form-label">Nomor HP</label>
            <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" required
                   value="{{ old('phone') }}" placeholder="Contoh: 081234567890" class="form-input">
        </div>

        <div>
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required class="form-input">
        </div>

        <div>
            <label for="password_confirmation" class="form-label">Ulangi Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="form-input">
        </div>

        <button type="submit" class="btn-primary">DAFTAR</button>

        <p class="text-center text-sm text-slate-600">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold text-sky-700 hover:underline">Masuk</a>
        </p>
    </form>
</x-layouts.app>
