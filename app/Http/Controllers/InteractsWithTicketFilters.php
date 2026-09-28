<?php

namespace App\Http\Controllers;

use App\Enums\VehicleClass;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Shared filter validation, vessel options and filter chips for the REPRINT and REKAP lists.
 */
trait InteractsWithTicketFilters
{
    /**
     * @return array{date_from: ?string, date_to: ?string, voyage_no: ?string, vessel: ?string, search: ?string, vehicle_class: ?string, ptosr: ?string}
     */
    protected function ticketFilters(Request $request, bool $defaultToToday): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'voyage_no' => ['nullable', 'string', 'max:50'],
            'vessel' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'vehicle_class' => ['nullable', Rule::enum(VehicleClass::class)],
            'ptosr' => ['nullable', Rule::in([Ticket::PTOSR_VERIFIED, Ticket::PTOSR_UNVERIFIED])],
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? $dateFrom;

        if ($defaultToToday && ! $dateFrom && ! $dateTo) {
            $dateFrom = $dateTo = now()->toDateString();
        }

        return [
            'date_from' => $dateFrom ?? $dateTo,
            'date_to' => $dateTo,
            'voyage_no' => $validated['voyage_no'] ?? null,
            'vessel' => $validated['vessel'] ?? null,
            'search' => $validated['search'] ?? null,
            'vehicle_class' => $validated['vehicle_class'] ?? null,
            'ptosr' => $validated['ptosr'] ?? null,
        ];
    }

    /**
     * Vessels that have tickets in the filtered date range, for the "Kapal" filter select.
     *
     * @param  array{date_from: ?string, date_to: ?string}  $filters
     * @return Collection<int, Ticket>
     */
    protected function voyageOptions(array $filters): Collection
    {
        return Ticket::query()
            ->filter(['date_from' => $filters['date_from'], 'date_to' => $filters['date_to']])
            ->select('voyage_no', 'vessel_name')
            ->distinct()
            ->orderBy('vessel_name')
            ->get();
    }

    /**
     * Chips describing the active filters; a null "remove" marks the non-removable default (today's date).
     *
     * @param  array{date_from: ?string, date_to: ?string, voyage_no: ?string, vehicle_class: ?string, ptosr: ?string}  $filters
     * @param  list<'date'|'voyage_no'|'ptosr'|'vehicle_class'>  $available
     * @param  Collection<int, Ticket>|null  $voyages
     * @return list<array{label: string, remove: ?list<string>}>
     */
    protected function filterChips(array $filters, array $available, ?Collection $voyages = null): array
    {
        $chips = [];

        if (in_array('date', $available, true) && $filters['date_from']) {
            $today = now()->toDateString();
            $isToday = $filters['date_from'] === $today && $filters['date_to'] === $today;
            $format = fn (string $date): string => Carbon::parse($date)->format('d/m/Y');

            $chips[] = [
                'label' => '📅 '.match (true) {
                    $isToday => 'Hari ini',
                    $filters['date_from'] === $filters['date_to'] => $format($filters['date_from']),
                    default => $format($filters['date_from']).' – '.$format($filters['date_to']),
                },
                'remove' => $isToday ? null : ['date_from', 'date_to'],
            ];
        }

        if (in_array('voyage_no', $available, true) && $filters['voyage_no']) {
            $vesselName = $voyages?->firstWhere('voyage_no', $filters['voyage_no'])?->vessel_name;
            $chips[] = ['label' => '🚢 '.($vesselName ?? $filters['voyage_no']), 'remove' => ['voyage_no']];
        }

        if (in_array('ptosr', $available, true) && $filters['ptosr']) {
            $chips[] = [
                'label' => $filters['ptosr'] === Ticket::PTOSR_VERIFIED ? '✔ PTOSR' : '⚠ NON PTOSR',
                'remove' => ['ptosr'],
            ];
        }

        if (in_array('vehicle_class', $available, true) && $filters['vehicle_class']) {
            $chips[] = ['label' => 'Gol. '.$filters['vehicle_class'], 'remove' => ['vehicle_class']];
        }

        return $chips;
    }
}
