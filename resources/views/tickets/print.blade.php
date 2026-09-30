<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kitir {{ $ticket->ticket_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        /* Thermal printers render thin strokes faintly, so the whole kitir uses a heavy sans-serif in pure black. */
        body { margin: 0; background: #e2e8f0; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .ticket { width: 80mm; margin: 16px auto; padding: 5mm 4mm; background: #fff; font-size: 13px; font-weight: 700; line-height: 1.4; }
        .center { text-align: center; }
        .title { font-size: 15px; font-weight: 900; white-space: nowrap; }
        .divider { border-top: 2px dashed #000; margin: 7px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        td.label { width: 34%; }
        .strong { font-weight: 900; }
        .big { font-size: 24px; font-weight: 900; letter-spacing: 1px; }
        .barcode { margin-top: 8px; text-align: center; }
        .barcode-value { margin-top: 2px; font-size: 12px; word-break: break-all; }
        .barcode img { display: block; max-width: 100%; max-height: 45mm; margin: 0 auto; image-rendering: pixelated; }
        .actions { width: 80mm; margin: 0 auto 24px; display: flex; gap: 8px; font-family: sans-serif; }
        .actions a, .actions button { flex: 1; padding: 10px; border: 0; border-radius: 8px; font-weight: bold; text-align: center; text-decoration: none; cursor: pointer; font-size: 14px; }
        .actions button { background: #0284c7; color: #fff; }
        .actions a { background: #334155; color: #fff; }
        @media print {
            body { background: #fff; }
            .ticket { margin: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            <div class="title">KITIR TIMBANGAN KENDARAAN</div>
            <div>RORO - PELABUHAN TANJUNG PERAK</div>
            <div>SURABAYA</div>
        </div>
        <div class="divider"></div>
        <table>
            <tr><td class="label">No Kitir</td><td>: {{ $ticket->ticket_number }}</td></tr>
            <tr><td class="label">Tanggal</td><td>: {{ $ticket->created_at->format('d/m/Y H:i') }}</td></tr>
            <tr><td class="label">Kapal</td><td>: {{ $ticket->vessel_name }}</td></tr>
            <tr><td class="label">Tujuan</td><td>: {{ $ticket->destination_port_name }}</td></tr>
            @if ($ticket->berth_name)
                <tr><td class="label">Dermaga</td><td>: {{ $ticket->berth_name }}</td></tr>
            @endif
        </table>
        <div class="divider"></div>
        <div class="center">
            <div>PLAT NOMOR</div>
            <div class="big">{{ $ticket->plate_number }}</div>
        </div>
        <div class="divider"></div>
        <table>
            <tr><td class="label">Golongan</td><td>: {{ $ticket->vehicle_class->value }}</td></tr>
            <tr><td class="label">Tonase</td><td>: <span class="strong">{{ $ticket->tonnageLabel() }}</span></td></tr>
            <tr><td class="label">Petugas</td><td>: {{ $ticket->user?->name }}</td></tr>
        </table>
        <div class="divider"></div>
        <div class="center">
            @if ($ticket->print_count > 1)
                <div>** CETAK ULANG KE-{{ $ticket->print_count - 1 }} **</div>
            @endif
            <div>Dicetak: {{ ($ticket->last_printed_at ?? now())->format('d/m/Y H:i') }}</div>
            <div>Simpan kitir ini sebagai bukti timbang</div>
        </div>
        @if ($ticket->photoUrl('barcode'))
            <div class="barcode">
                <img src="{{ $ticket->photoUrl('barcode') }}" alt="Barcode">
            </div>
        @endif
        @if ($ticket->barcode_value)
            <div class="center barcode-value">{{ $ticket->barcode_value }}</div>
        @endif
    </div>

    <div class="actions">
        <a href="{{ route('tickets.create') }}">← Input Baru</a>
        <button type="button" onclick="window.print()">🖨️ Cetak</button>
    </div>

    @if (session('autoprint'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
