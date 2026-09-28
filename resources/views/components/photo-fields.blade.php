@props([
    'idSuffix' => '',
    'vehicleLabel' => '📷 Foto Kendaraan',
    'ticketLabel' => '🧾 Foto Tiket',
    'vehiclePhotoUrl' => null,
    'ticketPhotoUrl' => null,
    'barcodeUrl' => null,
    'barcodeValue' => null,
    'barcodeFormat' => null,
])

@php
    $ticketInputId = 'ticket_photo'.$idSuffix;
    $isCreateForm = $idSuffix === '';
    $barcodeValue = $isCreateForm ? old('barcode_value', $barcodeValue) : $barcodeValue;
    $barcodeFormat = $isCreateForm ? old('barcode_format', $barcodeFormat) : $barcodeFormat;
@endphp

{{-- Relative source URL keeps the canvas same-origin even when APP_URL differs from the host used in the browser. --}}
<div class="flex flex-col gap-4" data-barcode-cropper="{{ $ticketInputId }}"
     data-source-url="{{ $ticketPhotoUrl ? parse_url($ticketPhotoUrl, PHP_URL_PATH) : '' }}">
    <div class="grid grid-cols-2 gap-3 sm:gap-4">
        <x-photo-input name="vehicle_photo" :id="'vehicle_photo'.$idSuffix" :label="$vehicleLabel" :current-url="$vehiclePhotoUrl" optional />
        <x-photo-input name="ticket_photo" :id="$ticketInputId" :label="$ticketLabel" :current-url="$ticketPhotoUrl" optional />
    </div>

    <section @class(['rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4', 'hidden' => ! $ticketPhotoUrl && blank($barcodeValue)]) data-barcode-panel>
        <div class="mb-3">
            <h3 class="text-sm font-bold tracking-wide text-slate-700 uppercase">Area Barcode</h3>
            <p class="text-xs text-slate-500">Tarik kotak di atas barcode pada foto tiket. Background akan dihapus otomatis.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <div class="flex items-center justify-center rounded-lg bg-slate-800/5 p-2">
                <div class="relative inline-block max-w-full cursor-crosshair touch-none select-none" data-barcode-stage>
                    <img alt="Foto tiket" class="block max-h-[55vh] w-auto max-w-full rounded" draggable="false" data-barcode-source>
                    <div class="pointer-events-none absolute hidden border-2 border-red-500 bg-red-500/10" data-barcode-selection></div>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-2 gap-2 text-xs font-semibold md:grid-cols-1">
                    <button type="button" class="col-span-2 rounded-lg bg-sky-600 px-3 py-2 text-white hover:bg-sky-700 disabled:opacity-60 md:col-span-1" data-barcode-detect>🔍 Deteksi Otomatis</button>
                    <button type="button" class="rounded-lg bg-slate-600 px-3 py-2 text-white hover:bg-slate-700" data-barcode-rotate>↻ Putar 90°</button>
                    <button type="button" class="rounded-lg bg-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-300" data-barcode-clear>✕ Hapus Pilihan</button>
                </div>

                <div class="flex flex-col gap-2 rounded-lg border border-dashed border-slate-300 bg-white p-3">
                    <p class="text-xs font-semibold text-slate-600">Hasil (dicetak di bawah tiket)</p>
                    <div class="flex min-h-20 items-center justify-center">
                        <img src="{{ $barcodeUrl }}" alt="Hasil barcode" loading="lazy" @class(['max-h-32 w-full object-contain', 'hidden' => ! $barcodeUrl]) data-barcode-preview>
                    </div>
                    <p class="text-xs text-slate-500" data-barcode-status>
                        {{ $barcodeUrl ? 'Barcode tersimpan. Pilih area baru untuk menggantinya.' : 'Belum ada barcode.' }}
                    </p>
                </div>

                <div>
                    <label for="barcode_value{{ $idSuffix }}" class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-600">
                        <span>Nilai Barcode</span>
                        <span class="rounded bg-slate-200 px-1.5 py-0.5 font-mono text-[10px] text-slate-600 empty:hidden" data-barcode-format-label>{{ $barcodeFormat }}</span>
                    </label>
                    <input id="barcode_value{{ $idSuffix }}" name="barcode_value" type="text" autocomplete="off"
                           value="{{ $barcodeValue }}" placeholder="Terisi otomatis dari scan / ketik manual"
                           class="form-input py-2 font-mono text-sm" data-barcode-value>
                    <input type="hidden" name="barcode_format" value="{{ $barcodeFormat }}" data-barcode-format>
                </div>
            </div>
        </div>
    </section>

    <input type="file" name="barcode_image" accept="image/png" class="hidden" data-barcode-output>
</div>
