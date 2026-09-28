<x-layouts.app :title="'Detail '.$ticket->plate_number">
    <x-report-tabs />

    <nav class="mb-4 flex flex-wrap items-center gap-x-2 text-sm font-semibold">
        <a href="{{ route('reports.vehicles') }}" class="text-sky-700 hover:underline">Kendaraan</a>
        <span class="text-slate-400">/</span>
        <a href="{{ route('reports.vessel', $ticket->voyage_no) }}" class="text-sky-700 hover:underline">🚢 {{ $ticket->vessel_name }}</a>
    </nav>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $ticket->plate_number }}</h2>
            <p class="text-sm text-slate-500">{{ $ticket->ticket_number }} · {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <x-ptosr-badge :verified="$ticket->isPtosrVerified()" class="px-3 py-1 text-sm" />
    </div>

    <section>
        <h3 class="mb-2 text-sm font-bold tracking-wide text-slate-600 uppercase">Data Kendaraan</h3>
        <dl class="grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 text-sm sm:grid-cols-2">
            @foreach ([
                'Kapal' => $ticket->vessel_name,
                'No Voyage' => $ticket->voyage_no,
                'Operator Kapal' => $ticket->operator_name,
                'Tujuan' => $ticket->destination_port_name,
                'Dermaga' => $ticket->berth_name,
                'Golongan' => $ticket->vehicle_class->label(),
                'Tonase' => $ticket->tonnageLabel().' ('.$ticket->weight_mode->label().')',
                'Nilai Barcode' => $ticket->barcode_value ? $ticket->barcode_value.($ticket->barcode_format ? " ({$ticket->barcode_format})" : '') : null,
                'Petugas Input' => $ticket->user ? "{$ticket->user->name} · {$ticket->user->phone}" : null,
                'Jumlah Cetak' => $ticket->print_count.'x'.($ticket->last_printed_at ? ', terakhir '.$ticket->last_printed_at->format('d/m/Y H:i') : ''),
            ] as $label => $value)
                <div class="bg-white px-3 py-2">
                    <dt class="text-xs text-slate-500">{{ $label }}</dt>
                    <dd class="font-medium break-all text-slate-800">{{ $value ?? '-' }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-3 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('tickets.reprint', $ticket) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700">🖨️ Cetak Ulang</button>
            </form>
        </div>
    </section>

    <section class="mt-8">
        <h3 class="mb-2 text-sm font-bold tracking-wide text-slate-600 uppercase">Foto</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ([
                'Foto Kendaraan' => $ticket->photoUrl('vehicle'),
                'Foto Tiket' => $ticket->photoUrl('ticket'),
                'Barcode' => $ticket->photoUrl('barcode'),
            ] as $label => $url)
                <figure class="flex flex-col gap-1">
                    @if ($url)
                        <a href="{{ $url }}" data-lightbox="{{ $label }} · {{ $ticket->plate_number }}" class="flex aspect-video items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                            <img src="{{ $url }}" alt="{{ $label }}" @class(['size-full', 'object-contain p-2' => $label === 'Barcode', 'object-cover' => $label !== 'Barcode'])>
                        </a>
                    @else
                        <div class="flex aspect-video items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-xs text-slate-400">Tidak ada</div>
                    @endif
                    <figcaption class="text-xs font-semibold text-slate-600">{{ $label }}</figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    <section class="mt-8 border-t border-slate-200 pt-6">
        <h3 class="mb-2 text-sm font-bold tracking-wide text-slate-600 uppercase">Verifikasi PTOSR</h3>

        @if ($ticket->isPtosrVerified())
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <p class="font-bold text-emerald-800">✔ Sudah diinput di PTOSR</p>
                    <form method="POST" action="{{ route('tickets.ptosr.destroy', $ticket) }}"
                          onsubmit="return confirm('Batalkan verifikasi PTOSR untuk {{ $ticket->plate_number }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50">Batalkan Verifikasi</button>
                    </form>
                </div>
                <dl class="mt-3 grid gap-3 text-emerald-900 sm:grid-cols-2">
                    @foreach ([
                        'Diverifikasi oleh' => $ticket->ptosrVerifier?->name,
                        'Waktu' => $ticket->ptosr_verified_at->format('d/m/Y H:i'),
                        'No. Referensi' => $ticket->ptosr_reference,
                        'Catatan' => $ticket->ptosr_note,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs text-emerald-700">{{ $label }}</dt>
                            <dd class="font-semibold break-all">{{ $value ?? '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @else
            <form method="POST" action="{{ route('tickets.ptosr.store', $ticket) }}"
                  class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                @csrf
                <div>
                    <p class="text-sm font-bold text-amber-800">⚠ Belum diverifikasi di PTOSR</p>
                    <p class="text-xs text-amber-700">Setelah kendaraan ini diinput di PTOSR, tandai di sini supaya statusnya berubah menjadi PTOSR.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="ptosr_reference" class="mb-1 block text-xs font-semibold text-slate-600">No. Referensi PTOSR (opsional)</label>
                        <input id="ptosr_reference" name="ptosr_reference" type="text" value="{{ old('ptosr_reference') }}" maxlength="50"
                               class="form-input py-2 text-sm" placeholder="No. tiket / transaksi PTOSR">
                    </div>
                    <div>
                        <label for="ptosr_note" class="mb-1 block text-xs font-semibold text-slate-600">Catatan (opsional)</label>
                        <input id="ptosr_note" name="ptosr_note" type="text" value="{{ old('ptosr_note') }}" maxlength="255" class="form-input py-2 text-sm">
                    </div>
                </div>
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">✔ Verifikasi: Sudah Diinput di PTOSR</button>
            </form>
        @endif
    </section>
</x-layouts.app>
