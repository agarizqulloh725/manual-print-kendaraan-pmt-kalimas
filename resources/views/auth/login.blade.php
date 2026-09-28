<x-layouts.app title="Login">
    <h2 class="mb-6 text-center text-lg font-bold text-slate-700">LOGIN</h2>

    <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
        @csrf

        <div>
            <label for="phone" class="form-label">Nomor HP</label>
            <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" required autofocus
                   value="{{ old('phone') }}" placeholder="Contoh: 081234567890" class="form-input">
        </div>

        <div>
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required class="form-input">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300">
            Ingat saya
        </label>

        <button type="submit" class="btn-primary">MASUK</button>

        <p class="text-center text-sm text-slate-500">
            Belum punya akun? Hubungi administrator.
        </p>
    </form>
</x-layouts.app>
