<x-layouts.app title="Input Timbangan">
    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6" data-ticket-form>
        @csrf

        <input type="hidden" name="vessel_code" value="{{ old('vessel_code') }}" data-vessel-field="vessel_code">
        <input type="hidden" name="vessel_name" value="{{ old('vessel_name') }}" data-vessel-field="vessel_name">
        <input type="hidden" name="operator_name" value="{{ old('operator_name') }}" data-vessel-field="operator_name">
        <input type="hidden" name="destination_port_code" value="{{ old('destination_port_code') }}" data-vessel-field="destination_port_code">
        <input type="hidden" name="berth_name" value="{{ old('berth_name') }}" data-vessel-field="berth_name">

        <div>
            <div class="flex items-center justify-between">
                <label for="voyage_no" class="form-label">Kapal Beroperasi</label>
                <button type="button" class="mb-2 text-xs font-semibold text-sky-700 hover:underline" data-vessel-refresh>↻ Muat ulang</button>
            </div>
            <select id="voyage_no" name="voyage_no" required class="form-input"
                    data-vessel-select data-url="{{ route('vessels.index') }}" data-old="{{ old('voyage_no') }}">
                <option value="">⏳ Memuat data kapal...</option>
            </select>
            <p class="mt-1 hidden text-xs text-slate-500" data-vessel-info></p>
        </div>

        <div>
            <label for="destination_port_name" class="form-label">Pelabuhan Tujuan</label>
            <input id="destination_port_name" name="destination_port_name" type="text" readonly required
                   value="{{ old('destination_port_name') }}" placeholder="Otomatis terisi..."
                   class="form-input bg-slate-100 text-slate-600" data-vessel-field="destination_port_name">
        </div>

        <div>
            <label for="plate_number" class="form-label">Plat Nomor Kendaraan</label>
            <input id="plate_number" name="plate_number" type="text" required autocomplete="off"
                   value="{{ old('plate_number') }}" placeholder="Contoh: L 1234 XY" class="form-input uppercase">
        </div>

        <div>
            <label for="vehicle_class" class="form-label">Golongan / Jenis</label>
            <select id="vehicle_class" name="vehicle_class" required class="form-input" data-vehicle-class>
                @foreach ($vehicleClasses as $vehicleClass)
                    <option value="{{ $vehicleClass->value }}" data-default-weight="{{ $vehicleClass->defaultWeightKg() }}"
                            @selected(old('vehicle_class', 'I') === $vehicleClass->value)>
                        {{ $vehicleClass->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <span class="form-label">Berat / Tonase (Kg)</span>
            <div class="mb-3 flex gap-6">
                @foreach ($weightModes as $weightMode)
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="radio" name="weight_mode" value="{{ $weightMode->value }}" class="size-4"
                               data-weight-mode @checked(old('weight_mode', 'manual') === $weightMode->value)>
                        {{ $weightMode->label() }}
                    </label>
                @endforeach
            </div>
            <input id="weight_kg" name="weight_kg" type="number" min="1" max="200000" inputmode="numeric"
                   value="{{ old('weight_kg') }}" placeholder="0" class="form-input" data-weight-input>
            <p class="mt-1 hidden text-xs text-slate-500" data-weight-hint>Berat otomatis diisi dari estimasi golongan kendaraan.</p>
        </div>

        <x-photo-fields ticket-label="🧾 Foto Tiket (opsional)" />

        <button type="submit" class="btn-primary">🖨️ SIMPAN &amp; CETAK TIKET</button>
    </form>
</x-layouts.app>
