<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_PARTIAL = 'partial';

    protected $fillable = [
        'trailer_id',
        'user_id',
        'customer_first_name',
        'customer_last_name',
        'customer_phone',
        'customer_email',
        'customer_number',
        'company_name',

        // New date-based period
        'start_date',
        'end_date',

        'status',
        'payment_status',
        'partial_paid_amount',

        'service_selber_beladen',
        'service_lehr',
        'service_paket',

        // Legacy (kept for compatibility with older data)
        'starts_at',
        'ends_at',

        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',

        'service_selber_beladen' => 'boolean',
        'service_lehr' => 'boolean',
        'service_paket' => 'boolean',
        'partial_paid_amount' => 'decimal:2',
    ];

    public function trailer(): BelongsTo
    {
        return $this->belongsTo(Trailer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Overlap check for the new date-based model.
     * We treat end_date as exclusive for overlap math.
     */
    public function scopeOverlappingDates(Builder $query, CarbonInterface $startDate, CarbonInterface $endDateExclusive): Builder
    {
        return $query->whereDate('start_date', '<', $endDateExclusive->toDateString())
            ->whereDate('end_date', '>', $startDate->toDateString());
    }

    /** Legacy overlap for datetime-based reservations (kept for compatibility). */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);
    }

    public function isPending(): bool
    {
        return ($this->status ?? self::STATUS_CONFIRMED) === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return ($this->payment_status ?? self::PAYMENT_UNPAID) === self::PAYMENT_PAID;
    }

    public function isPartiallyPaid(): bool
    {
        return ($this->payment_status ?? self::PAYMENT_UNPAID) === self::PAYMENT_PARTIAL;
    }

    /**
     * Helper to get a printable date range regardless of legacy/new columns.
     */
    public function periodStartDate(): ?CarbonInterface
    {
        if ($this->start_date) {
            return Carbon::parse($this->start_date);
        }
        return $this->starts_at;
    }

    public function periodEndDate(): ?CarbonInterface
    {
        if ($this->end_date) {
            return Carbon::parse($this->end_date);
        }
        return $this->ends_at;
    }
}
