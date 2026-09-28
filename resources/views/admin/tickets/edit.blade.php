<x-layouts.admin :title="'Edit '.$ticket->ticket_number">
    <a href="{{ route('admin.tickets.index', ['date_from' => $ticket->created_at->toDateString(), 'date_to' => $ticket->created_at->toDateString()]) }}"
       class="mb-3 inline-block text-sm font-semibold text-sky-700 hover:underline">← Semua tiket</a>

    <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" class="rounded-xl bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')

        <div class="mb-5">
            <h2 class="text-xl font-bold text-slate-800">Edit Tiket {{ $ticket->ticket_number }}</h2>
            <p class="text-sm text-slate-500">
                {{ $ticket->created_at->format('d/m/Y H:i') }} · 🚢 {{ $ticket->vessel_name }} ({{ $ticket->voyage_no }}) · Petugas {{ $ticket->user?->name ?? '-' }}
            </p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="plate_number" class="form-label">Plat Nomor</label>
                <input id="plate_number" name="plate_number" type="text" required value="{{ old('plate_number', $ticket->plate_number) }}" class="form-input uppercase">
            </div>

            <div>
                <label for="destination_port_name" class="form-label">Pelabuhan Tujuan</label>
                <input id="destination_port_name" name="destination_port_name" type="text" required
                       value="{{ old('destination_port_name', $ticket->destination_port_name) }}" class="form-input uppercase">
            </div>

            <div>
                <label for="vehicle_class" class="form-label">Golongan</label>
                <select id="vehicle_class" name="vehicle_class" required class="form-input">
                    @foreach ($vehicleClasses as $vehicleClass)
                        <option value="{{ $vehicleClass->value }}" @selected(old('vehicle_class', $ticket->vehicle_class->value) === $vehicleClass->value)>{{ $vehicleClass->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-[1fr_auto] gap-3">
                <div>
                    <label for="weight_ton" class="form-label">Tonase (Ton)</label>
                    <div class="relative">
                        <input id="weight_ton" name="weight_ton" type="text" inputmode="decimal" required autocomplete="off"
                               value="{{ old('weight_ton', str_replace('.', ',', (string) $ticket->weight_ton)) }}" class="form-input pr-14">
                        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-bold text-slate-500">Ton</span>
                    </div>
                </div>
                <div>
                    <label for="weight_mode" class="form-label">Mode</label>
                    <select id="weight_mode" name="weight_mode" class="form-input">
                        @foreach ($weightModes as $weightMode)
                            <option value="{{ $weightMode->value }}" @selected(old('weight_mode', $ticket->weight_mode->value) === $weightMode->value)>{{ $weightMode->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="sm:col-span-2">
                <label for="barcode_value" class="form-label">Nilai Barcode</label>
                <input id="barcode_value" name="barcode_value" type="text" value="{{ old('barcode_value', $ticket->barcode_value) }}" class="form-input font-mono">
                <input type="hidden" name="barcode_format" value="{{ old('barcode_format', $ticket->barcode_format) }}">
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-500">Foto dan verifikasi PTOSR dikelola dari halaman
            <a href="{{ route('tickets.show', $ticket) }}" class="font-semibold text-sky-700 hover:underline">detail kendaraan</a>.
        </p>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.tickets.index') }}" class="rounded-lg bg-slate-100 px-4 py-3 font-bold text-slate-700 hover:bg-slate-200">Batal</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</x-layouts.admin>
