<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\VesselController;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Monitoring overview for administrators.
 */
class DashboardController extends Controller
{
    private const TREND_DAYS = 7;

    public function index(): View
    {
        $today = ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()];

        $todayTotals = Ticket::query()
            ->filter($today)
            ->selectRaw('COUNT(*) as tickets, COUNT(DISTINCT user_id) as operators')
            ->selectRaw('COALESCE(SUM(CASE WHEN ptosr_verified_at IS NOT NULL THEN 1 ELSE 0 END), 0) as ptosr')
            ->toBase()
            ->first();

        return view('admin.dashboard', [
            'stats' => [
                'tickets_today' => (int) $todayTotals->tickets,
                'ptosr_today' => (int) $todayTotals->ptosr,
                'operators_today' => (int) $todayTotals->operators,
                'non_ptosr_backlog' => Ticket::query()->whereNull('ptosr_verified_at')->count(),
                'active_users' => User::query()->where('is_active', true)->count(),
                'inactive_users' => User::query()->where('is_active', false)->count(),
            ],
            'trend' => $this->trend(),
            'operatorActivity' => Ticket::query()
                ->filter($today)
                ->with('user:id,name,phone')
                ->selectRaw('user_id, COUNT(*) as total, MAX(created_at) as last_input_at')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->get(),
            'vesselsToday' => Ticket::query()
                ->filter($today)
                ->selectRaw('voyage_no, vessel_name, destination_port_name, COUNT(*) as total, SUM(weight_ton) as total_weight_ton')
                ->selectRaw('SUM(CASE WHEN ptosr_verified_at IS NOT NULL THEN 1 ELSE 0 END) as ptosr')
                ->groupBy('voyage_no', 'vessel_name', 'destination_port_name')
                ->orderByDesc('total')
                ->get(),
            'recentTickets' => Ticket::query()->with('user:id,name')->latest()->limit(8)->get(),
            'apiStatus' => Cache::get(VesselController::STATUS_CACHE_KEY),
        ]);
    }

    /**
     * Tickets per day for the last week, including days without tickets.
     *
     * @return list<array{date: Carbon, total: int, ptosr: int}>
     */
    private function trend(): array
    {
        $start = now()->subDays(self::TREND_DAYS - 1)->startOfDay();

        $rows = Ticket::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN ptosr_verified_at IS NOT NULL THEN 1 ELSE 0 END) as ptosr')
            ->groupBy('day')
            ->toBase()
            ->get()
            ->keyBy('day');

        return collect(range(0, self::TREND_DAYS - 1))
            ->map(function (int $offset) use ($start, $rows): array {
                $date = $start->copy()->addDays($offset);
                $row = $rows->get($date->toDateString());

                return [
                    'date' => $date,
                    'total' => (int) ($row->total ?? 0),
                    'ptosr' => (int) ($row->ptosr ?? 0),
                ];
            })
            ->all();
    }
}
