<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class VesselController extends Controller
{
    /**
     * Result of the last real call to PTOS-R, shown on the admin monitoring dashboard.
     */
    public const STATUS_CACHE_KEY = 'ptosr.status';

    /**
     * Operating vessels from the PTOS-R schedule board, fetched server-side and cached briefly.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('refresh')) {
            Cache::forget('ptosr.vessels');
        }

        try {
            $vessels = Cache::remember(
                'ptosr.vessels',
                config('services.ptosr.cache_seconds'),
                function (): array {
                    $vessels = $this->fetchVessels();

                    Cache::forever(self::STATUS_CACHE_KEY, [
                        'ok' => true,
                        'checked_at' => now()->toIso8601String(),
                        'vessels' => count($vessels),
                    ]);

                    return $vessels;
                },
            );
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            report($exception);

            Cache::forever(self::STATUS_CACHE_KEY, [
                'ok' => false,
                'checked_at' => now()->toIso8601String(),
                'error' => class_basename($exception),
            ]);

            return response()->json([
                'message' => 'Gagal mengambil data kapal dari PTOS-R. Silakan coba lagi.',
            ], 502);
        }

        return response()->json(['data' => $vessels]);
    }

    /**
     * @return list<array{voyage_no: string, vessel_code: ?string, vessel_name: string, operator_name: ?string, destination_port_code: ?string, destination_port_name: string, berth_name: ?string, eta: ?string, etd: ?string, status: ?string}>
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws UnexpectedValueException
     */
    private function fetchVessels(): array
    {
        $schedules = Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->retry([200, 1000], 0, fn (\Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && $exception->response->serverError()))
            ->get(config('services.ptosr.schedule_url'), [
                'kd_cabang' => config('services.ptosr.branch_code'),
                'kd_terminal' => config('services.ptosr.terminal_code'),
            ])
            ->throw()
            ->json();

        if (! is_array($schedules) || ! array_is_list($schedules)) {
            throw new UnexpectedValueException('PTOS-R schedule board returned an unexpected payload.');
        }

        return collect($schedules)
            ->filter(fn (mixed $schedule): bool => is_array($schedule) && filled($schedule['VOYAGE_NO'] ?? null) && filled($schedule['NAMA_KAPAL'] ?? null))
            ->map(fn (array $schedule): array => [
                'voyage_no' => $schedule['VOYAGE_NO'],
                'vessel_code' => $schedule['KD_KAPAL'] ?? null,
                'vessel_name' => $schedule['NAMA_KAPAL'],
                'operator_name' => $schedule['NM_OPERATOR'] ?? null,
                'destination_port_code' => $schedule['KD_PORT_DEST'] ?? null,
                'destination_port_name' => mb_strtoupper($schedule['NM_PORT_DEST'] ?? $schedule['NM_PORT_NEXT'] ?? '-'),
                'berth_name' => $schedule['NM_DERMAGA'] ?? null,
                'eta' => $schedule['ETA'] ?? null,
                'etd' => $schedule['ETD'] ?? null,
                'status' => $schedule['VESOPS_STATUS'] ?? null,
            ])
            ->sortBy('etd')
            ->values()
            ->all();
    }
}
