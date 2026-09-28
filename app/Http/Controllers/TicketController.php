<?php

namespace App\Http\Controllers;

use App\Enums\VehicleClass;
use App\Enums\WeightMode;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TicketController extends Controller
{
    use InteractsWithTicketFilters;

    /**
     * Each reprint card carries a photo/barcode form, so keep pages short.
     */
    private const REPRINT_PER_PAGE = 10;

    /**
     * INPUT tab: weighing form.
     */
    public function create(): View
    {
        return view('tickets.create', [
            'vehicleClasses' => VehicleClass::cases(),
            'weightModes' => WeightMode::cases(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $vehiclePhotoPath = $this->storePhoto($request->file('vehicle_photo'), 'vehicle');
        $ticketPhotoPath = $this->storePhoto($request->file('ticket_photo'), 'ticket');
        $barcodePath = $ticketPhotoPath ? $this->storePhoto($request->file('barcode_image'), 'barcode') : null;

        $ticket = DB::transaction(function () use ($request, $vehiclePhotoPath, $ticketPhotoPath, $barcodePath): Ticket {
            $ticket = $request->user()->tickets()->create([
                ...$request->safe()->except(['weight_ton', 'vehicle_photo', 'ticket_photo', 'barcode_image']),
                'ticket_number' => Ticket::nextTicketNumber(),
                'weight_ton' => $request->weightTon(),
                'vehicle_photo_path' => $vehiclePhotoPath,
                'ticket_photo_path' => $ticketPhotoPath,
                'barcode_path' => $barcodePath,
            ]);

            $ticket->markPrinted();

            return $ticket;
        });

        return redirect()
            ->route('tickets.print', $ticket)
            ->with('autoprint', true);
    }

    /**
     * REPRINT tab: search and reprint previously issued tickets.
     */
    public function index(Request $request): View
    {
        $filters = $this->ticketFilters($request, defaultToToday: true);
        $voyages = $this->voyageOptions($filters);

        $tickets = Ticket::query()
            ->with('user:id,name')
            ->filter($filters)
            ->latest()
            ->paginate(self::REPRINT_PER_PAGE)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'chips' => $this->filterChips($filters, ['date', 'voyage_no', 'ptosr', 'vehicle_class'], $voyages),
            'voyages' => $voyages,
            'vehicleClasses' => VehicleClass::cases(),
        ]);
    }

    /**
     * Vehicle detail with photos, barcode and PTOSR verification.
     */
    public function show(Ticket $ticket): View
    {
        return view('tickets.show', [
            'ticket' => $ticket->load(['user:id,name,phone', 'ptosrVerifier:id,name']),
        ]);
    }

    public function print(Ticket $ticket): View
    {
        return view('tickets.print', [
            'ticket' => $ticket->load('user:id,name'),
        ]);
    }

    public function reprint(Ticket $ticket): RedirectResponse
    {
        $ticket->markPrinted();

        return redirect()
            ->route('tickets.print', $ticket)
            ->with('autoprint', true);
    }

    private function storePhoto(?UploadedFile $photo, string $folder): ?string
    {
        return $photo?->store("tickets/{$folder}/".now()->format('Y-m'), 'public');
    }
}
