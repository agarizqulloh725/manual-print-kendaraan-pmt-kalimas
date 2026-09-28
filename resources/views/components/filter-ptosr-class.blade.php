@props(['filters', 'vehicleClasses'])

<div>
    <span class="form-label">Status PTOSR</span>
    <div class="grid grid-cols-3 gap-2 text-sm font-semibold">
        @foreach (['' => 'Semua', \App\Models\Ticket::PTOSR_VERIFIED => '✔ PTOSR', \App\Models\Ticket::PTOSR_UNVERIFIED => '⚠ NON'] as $value => $label)
            <label class="cursor-pointer">
                <input type="radio" name="ptosr" value="{{ $value }}" class="peer sr-only" @checked(($filters['ptosr'] ?? '') === (string) $value)>
                <span class="block rounded-lg border border-slate-300 px-2 py-2 text-center text-slate-600 peer-checked:border-sky-600 peer-checked:bg-sky-50 peer-checked:text-sky-700 peer-focus-visible:ring-2 peer-focus-visible:ring-sky-300">{{ $label }}</span>
            </label>
        @endforeach
    </div>
</div>

<div>
    <label for="vehicle_class" class="form-label">Golongan</label>
    <select id="vehicle_class" name="vehicle_class" class="form-input">
        <option value="">Semua golongan</option>
        @foreach ($vehicleClasses as $vehicleClass)
            <option value="{{ $vehicleClass->value }}" @selected($filters['vehicle_class'] === $vehicleClass->value)>{{ $vehicleClass->label() }}</option>
        @endforeach
    </select>
</div>
