<?php

namespace App\Http\Controllers;

use App\Enums\VehicleClass;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use InteractsWithTicketFilters;

    /**
     * REKAP > Kapal: one row per voyage with vehicle, weight and PTOSR totals.
     */
    public function vessels(Request $request): View
    {
        $filters = $this->ticketFilters($request, defaultToToday: true);

        $vessels = Ticket::query()
            ->filter($filters)
            ->selectRaw('voyage_no, vessel_name, destination_port_name')
            ->selectRaw('COUNT(*) as total_vehicles, SUM(weight_ton) as total_weight_ton')
            ->selectRaw('SUM(CASE WHEN ptosr_verified_at IS NOT NULL THEN 1 ELSE 0 END) as ptosr_vehicles')
            ->selectRaw('MAX(created_at) as last_input_at')
            ->groupBy('voyage_no', 'vessel_name', 'destination_port_name')
            ->orderByDesc('last_input_at')
            ->paginate(20)
            ->withQueryString();

        return view('reports.vessels', [
            'filters' => $filters,
            'chips' => $this->filterChips($filters, ['date']),
            'vessels' => $vessels,
        ]);
    }

    /**
     * REKAP > Kapal > detail: every vehicle weighed for one voyage.
     */
    public function vessel(Request $request, string $voyageNo): View
    {
        $filters = [...$this->ticketFilters($request, defaultToToday: false), 'voyage_no' => $voyageNo];

        $vessel = Ticket::query()->where('voyage_no', $voyageNo)->latest()->firstOrFail();

        $tickets = Ticket::query()
            ->filter($filters)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('reports.vessel', [
            'filters' => $filters,
            'chips' => $this->filterChips($filters, ['ptosr', 'vehicle_class']),
            'vessel' => $vessel,
            'tickets' => $tickets,
            'totals' => $this->totals(Ticket::query()->where('voyage_no', $voyageNo)),
            'vehicleClasses' => VehicleClass::cases(),
        ]);
    }

    /**
     * REKAP > Kendaraan: every weighed vehicle in the selected period.
     */
    public function vehicles(Request $request): View
    {
        $filters = $this->ticketFilters($request, defaultToToday: true);

        $tickets = Ticket::query()
            ->filter($filters)
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $voyages = $this->voyageOptions($filters);

        return view('reports.vehicles', [
            'filters' => $filters,
            'chips' => $this->filterChips($filters, ['date', 'voyage_no', 'ptosr', 'vehicle_class'], $voyages),
            'tickets' => $tickets,
            'voyages' => $voyages,
            'totals' => $this->totals(Ticket::query()->filter($filters)),
            'vehicleClasses' => VehicleClass::cases(),
        ]);
    }

    /**
     * Export the filtered tickets as a CSV file that opens directly in Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->ticketFilters($request, defaultToToday: blank($request->input('voyage_no')));

        $fileName = 'rekap-timbangan-'.($filters['date_from']
            ? "{$filters['date_from']}_{$filters['date_to']}"
            : $filters['voyage_no']).'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'w');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'No', 'No Tiket', 'Tanggal', 'Jam', 'Kapal', 'No Voyage', 'Operator Kapal', 'Pelabuhan Tujuan',
                'Dermaga', 'Plat Nomor', 'Golongan', 'Mode Berat', 'Tonase (Ton)', 'Jumlah Cetak',
                'Petugas', 'Nilai Barcode', 'Format Barcode', 'Status PTOSR', 'Diverifikasi Oleh',
                'Waktu Verifikasi', 'Ref PTOSR', 'Catatan PTOSR', 'Foto Kendaraan', 'Foto Tiket', 'Gambar Barcode',
            ], ';');

            $rowNumber = 0;

            Ticket::query()
                ->with(['user:id,name', 'ptosrVerifier:id,name'])
                ->filter($filters)
                ->orderBy('created_at')
                ->lazyById(500)
                ->each(function (Ticket $ticket) use ($output, &$rowNumber): void {
                    fputcsv($output, [
                        ++$rowNumber,
                        $ticket->ticket_number,
                        $ticket->created_at->format('d/m/Y'),
                        $ticket->created_at->format('H:i'),
                        $ticket->vessel_name,
                        $ticket->voyage_no,
                        $ticket->operator_name,
                        $ticket->destination_port_name,
                        $ticket->berth_name,
                        $ticket->plate_number,
                        $ticket->vehicle_class->value,
                        $ticket->weight_mode->label(),
                        number_format((float) $ticket->weight_ton, 2, ',', ''),
                        $ticket->print_count,
                        $ticket->user?->name,
                        $ticket->barcode_value,
                        $ticket->barcode_format,
                        $ticket->isPtosrVerified() ? 'PTOSR' : 'NON PTOSR',
                        $ticket->ptosrVerifier?->name,
                        $ticket->ptosr_verified_at?->format('d/m/Y H:i'),
                        $ticket->ptosr_reference,
                        $ticket->ptosr_note,
                        $ticket->photoUrl('vehicle'),
                        $ticket->photoUrl('ticket'),
                        $ticket->photoUrl('barcode'),
                    ], ';');
                });

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return array{vehicles: int, weight_ton: float, ptosr: int}
     */
    private function totals(Builder $query): array
    {
        $totals = $query
            ->selectRaw('COUNT(*) as vehicles, COALESCE(SUM(weight_ton), 0) as weight_ton')
            ->selectRaw('COALESCE(SUM(CASE WHEN ptosr_verified_at IS NOT NULL THEN 1 ELSE 0 END), 0) as ptosr')
            ->toBase()
            ->first();

        return [
            'vehicles' => (int) $totals->vehicles,
            'weight_ton' => (float) $totals->weight_ton,
            'ptosr' => (int) $totals->ptosr,
        ];
    }
}
