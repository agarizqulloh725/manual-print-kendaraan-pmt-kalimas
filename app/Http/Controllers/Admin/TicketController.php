<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VehicleClass;
use App\Enums\WeightMode;
use App\Http\Controllers\Controller;
use App\Http\Controllers\InteractsWithTicketFilters;
use App\Http\Requests\Admin\UpdateTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Ticket management: correct mistakes in issued tickets or remove wrong entries.
 */
class TicketController extends Controller
{
    use InteractsWithTicketFilters;

    public function index(Request $request): View
    {
        $filters = $this->ticketFilters($request, defaultToToday: true);
        $voyages = $this->voyageOptions($filters);

        $tickets = Ticket::query()
            ->with('user:id,name')
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'chips' => $this->filterChips($filters, ['date', 'voyage_no', 'ptosr', 'vehicle_class'], $voyages),
            'voyages' => $voyages,
            'vehicleClasses' => VehicleClass::cases(),
        ]);
    }

    public function edit(Ticket $ticket): View
    {
        return view('admin.tickets.edit', [
            'ticket' => $ticket->load('user:id,name'),
            'vehicleClasses' => VehicleClass::cases(),
            'weightModes' => WeightMode::cases(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validated();

        // A hand-corrected barcode value no longer matches the scanned format.
        if ($data['barcode_value'] !== $ticket->barcode_value) {
            $data['barcode_format'] = null;
        }

        $ticket->update($data);

        return redirect()
            ->route('admin.tickets.index', ['date_from' => $ticket->created_at->toDateString(), 'date_to' => $ticket->created_at->toDateString()])
            ->with('status', "Tiket {$ticket->ticket_number} berhasil diperbarui.");
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $photoPaths = array_filter(array_map($ticket->photoPath(...), array_keys(Ticket::PHOTO_KINDS)));

        $ticket->delete();

        Storage::disk('public')->delete($photoPaths);

        return back()->with('status', "Tiket {$ticket->ticket_number} ({$ticket->plate_number}) berhasil dihapus.");
    }
}
