<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyPtosrRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

/**
 * Marks that a manually weighed vehicle has also been entered in PTOSR.
 */
class PtosrVerificationController extends Controller
{
    public function store(VerifyPtosrRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->forceFill([
            ...$request->validated(),
            'ptosr_verified_at' => now(),
            'ptosr_verified_by' => $request->user()->id,
        ])->save();

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Kendaraan {$ticket->plate_number} ditandai sudah diinput di PTOSR.");
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $ticket->forceFill([
            'ptosr_verified_at' => null,
            'ptosr_verified_by' => null,
            'ptosr_reference' => null,
            'ptosr_note' => null,
        ])->save();

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Verifikasi PTOSR untuk {$ticket->plate_number} dibatalkan.");
    }
}
