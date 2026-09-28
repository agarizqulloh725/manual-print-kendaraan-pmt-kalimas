<?php

namespace App\Models;

use App\Enums\VehicleClass;
use App\Enums\WeightMode;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'ticket_number',
    'voyage_no',
    'vessel_code',
    'vessel_name',
    'operator_name',
    'destination_port_code',
    'destination_port_name',
    'berth_name',
    'plate_number',
    'vehicle_class',
    'weight_mode',
    'weight_ton',
    'vehicle_photo_path',
    'ticket_photo_path',
    'barcode_path',
    'barcode_value',
    'barcode_format',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    public const PTOSR_VERIFIED = 'ptosr';

    public const PTOSR_UNVERIFIED = 'non_ptosr';

    /**
     * The images a ticket can have, keyed by the kind used in URLs, mapped to their database column.
     * "barcode" is the background-free barcode cut out of the ticket photo, printed at the bottom of the ticket.
     *
     * @var array<'vehicle'|'ticket'|'barcode', string>
     */
    public const PHOTO_KINDS = [
        'vehicle' => 'vehicle_photo_path',
        'ticket' => 'ticket_photo_path',
        'barcode' => 'barcode_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vehicle_class' => VehicleClass::class,
            'weight_mode' => WeightMode::class,
            'weight_ton' => 'decimal:2',
            'print_count' => 'integer',
            'last_printed_at' => 'datetime',
            'ptosr_verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The operator who confirmed the vehicle was also entered in PTOSR.
     *
     * @return BelongsTo<User, $this>
     */
    public function ptosrVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ptosr_verified_by');
    }

    public function isPtosrVerified(): bool
    {
        return $this->ptosr_verified_at !== null;
    }

    /**
     * Build the next sequential ticket number for today, e.g. TMB-20260928-0001.
     */
    public static function nextTicketNumber(): string
    {
        $prefix = 'TMB-'.now()->format('Ymd').'-';

        $lastNumber = static::query()
            ->where('ticket_number', 'like', $prefix.'%')
            ->max('ticket_number');

        $sequence = $lastNumber ? ((int) substr($lastNumber, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Apply the optional report filters.
     *
     * @param  Builder<Ticket>  $query
     * @param  array{date_from?: ?string, date_to?: ?string, voyage_no?: ?string, vessel?: ?string, search?: ?string, vehicle_class?: ?string, ptosr?: ?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->where('created_at', '<=', Carbon::parse($date)->endOfDay()))
            ->when($filters['voyage_no'] ?? null, fn (Builder $query, string $voyageNo) => $query->where('voyage_no', $voyageNo))
            ->when($filters['vessel'] ?? null, function (Builder $query, string $vessel) {
                $query->where(function (Builder $query) use ($vessel) {
                    $query->where('vessel_name', 'like', '%'.$vessel.'%')
                        ->orWhere('voyage_no', 'like', '%'.$vessel.'%')
                        ->orWhere('destination_port_name', 'like', '%'.$vessel.'%');
                });
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('plate_number', 'like', '%'.$search.'%')
                        ->orWhere('ticket_number', 'like', '%'.$search.'%')
                        ->orWhere('barcode_value', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['vehicle_class'] ?? null, fn (Builder $query, string $vehicleClass) => $query->where('vehicle_class', $vehicleClass))
            ->when(($filters['ptosr'] ?? null) === self::PTOSR_VERIFIED, fn (Builder $query) => $query->whereNotNull('ptosr_verified_at'))
            ->when(($filters['ptosr'] ?? null) === self::PTOSR_UNVERIFIED, fn (Builder $query) => $query->whereNull('ptosr_verified_at'));
    }

    /**
     * The ticket weight, e.g. "12,50 Ton".
     */
    public function tonnageLabel(): string
    {
        return self::formatTon($this->weight_ton);
    }

    /**
     * Format a weight in tonnes the Indonesian way: 1 → "1,00 Ton", 12.5 → "12,50 Ton".
     */
    public static function formatTon(float|int|string|null $tonnes): string
    {
        return number_format((float) $tonnes, 2, ',', '.').' Ton';
    }

    /**
     * Stored path (relative to the public disk) of one photo kind, or null when it was not taken.
     */
    public function photoPath(string $kind): ?string
    {
        $column = self::PHOTO_KINDS[$kind] ?? null;

        return $column ? $this->{$column} : null;
    }

    /**
     * THE way to show any ticket image (vehicle photo, ticket photo, barcode), everywhere in the app.
     *
     * The URL points to MediaController, which reads the file from storage itself. So images work on
     * shared hosting without the public/storage symlink, on whatever domain the page was opened, and only
     * for logged-in users. "v" changes whenever the stored file changes, so browsers never show a stale cache.
     */
    public function photoUrl(string $kind): ?string
    {
        $path = $this->photoPath($kind);

        return $path ? route('tickets.photo', ['ticket' => $this, 'kind' => $kind, 'v' => substr(md5($path), 0, 8)]) : null;
    }

    /**
     * How many of the three images exist for this ticket.
     */
    public function photoCount(): int
    {
        return collect(array_keys(self::PHOTO_KINDS))->filter(fn (string $kind) => $this->photoPath($kind))->count();
    }

    /**
     * Content type from the file extension. Deliberately not using the fileinfo extension,
     * which is missing or disabled on some shared hosting.
     */
    public static function photoMimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }

    public function markPrinted(): void
    {
        $this->print_count++;
        $this->last_printed_at = now();
        $this->save();
    }
}
