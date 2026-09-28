{{-- Shared fields for creating and editing a user. Expects $roles and optionally $user. --}}
@php($user ??= null)

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="name" class="form-label">Nama Lengkap</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $user?->name) }}" class="form-input">
    </div>

    <div>
        <label for="phone" class="form-label">Nomor HP (untuk login)</label>
        <input id="phone" name="phone" type="tel" inputmode="numeric" required value="{{ old('phone', $user?->phone) }}"
               placeholder="Contoh: 081234567890" class="form-input">
    </div>

    <div>
        <span class="form-label">Peran</span>
        <div class="grid grid-cols-2 gap-2 text-sm font-semibold">
            @foreach ($roles as $role)
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="{{ $role->value }}" class="peer sr-only"
                           @checked(old('role', $user?->role->value ?? 'operator') === $role->value)>
                    <span class="block rounded-lg border border-slate-300 px-3 py-3 text-center text-slate-600 peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:text-sky-700 peer-focus-visible:ring-2 peer-focus-visible:ring-sky-300">
                        {{ $role->label() }}
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    @if ($user)
        <div>
            <span class="form-label">Status Akun</span>
            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-300 px-4 py-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="size-5 accent-sky-600" @checked(old('is_active', $user->is_active))>
                <span class="text-sm font-semibold text-slate-700">Aktif (boleh login)</span>
            </label>
        </div>
    @endif

    <div>
        <label for="password" class="form-label">{{ $user ? 'Password Baru (kosongkan jika tidak diganti)' : 'Password' }}</label>
        <input id="password" name="password" type="password" autocomplete="new-password" @required(! $user) class="form-input">
    </div>

    <div>
        <label for="password_confirmation" class="form-label">Ulangi Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $user) class="form-input">
    </div>
</div>
