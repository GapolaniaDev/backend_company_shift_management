<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReplacementBid extends Model
{
    use HasFactory;

    protected $fillable = [
        'replacement_request_id',
        'bidder_employee_id',
        'bid_status',
        'bid_amount',
        'message',
        'bid_at',
        'responded_at',
        'responded_by',
        'response_notes',
        'priority_rank',
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
        'bid_at' => 'datetime',
        'responded_at' => 'datetime',
        'priority_rank' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'bid_status' => 'pending',
        'bid_at' => 'now',
    ];

    /**
     * The possible bid statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_WITHDRAWN,
    ];

    /**
     * Get the replacement request this bid belongs to
     */
    public function replacementRequest(): BelongsTo
    {
        return $this->belongsTo(ReplacementRequest::class);
    }

    /**
     * Get the employee who made this bid
     */
    public function bidderEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'bidder_employee_id');
    }

    /**
     * Get the employee who responded to this bid
     */
    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responded_by');
    }

    /**
     * Get the shift through the replacement request
     */
    public function shift(): BelongsTo
    {
        return $this->replacementRequest->shiftAssignment->shift();
    }

    /**
     * Get the company through the replacement request
     */
    public function company(): BelongsTo
    {
        return $this->replacementRequest->company();
    }

    /**
     * Scope bids by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('bid_status', $status);
    }

    /**
     * Scope pending bids
     */
    public function scopePending($query)
    {
        return $query->where('bid_status', self::STATUS_PENDING);
    }

    /**
     * Scope accepted bids
     */
    public function scopeAccepted($query)
    {
        return $query->where('bid_status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope rejected bids
     */
    public function scopeRejected($query)
    {
        return $query->where('bid_status', self::STATUS_REJECTED);
    }

    /**
     * Scope withdrawn bids
     */
    public function scopeWithdrawn($query)
    {
        return $query->where('bid_status', self::STATUS_WITHDRAWN);
    }

    /**
     * Scope bids by employee
     */
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('bidder_employee_id', $employeeId);
    }

    /**
     * Scope bids with amounts
     */
    public function scopeWithAmount($query)
    {
        return $query->whereNotNull('bid_amount');
    }

    /**
     * Scope bids ordered by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority_rank')
                    ->orderBy('bid_amount', 'desc')
                    ->orderBy('bid_at');
    }

    /**
     * Check if bid is pending
     */
    public function isPending(): bool
    {
        return $this->bid_status === self::STATUS_PENDING;
    }

    /**
     * Check if bid is accepted
     */
    public function isAccepted(): bool
    {
        return $this->bid_status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if bid is rejected
     */
    public function isRejected(): bool
    {
        return $this->bid_status === self::STATUS_REJECTED;
    }

    /**
     * Check if bid is withdrawn
     */
    public function isWithdrawn(): bool
    {
        return $this->bid_status === self::STATUS_WITHDRAWN;
    }

    /**
     * Accept the bid
     */
    public function accept(Employee $respondedBy, string $notes = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        // Mark other bids for same request as rejected
        self::where('replacement_request_id', $this->replacement_request_id)
            ->where('id', '!=', $this->id)
            ->where('bid_status', self::STATUS_PENDING)
            ->update([
                'bid_status' => self::STATUS_REJECTED,
                'responded_at' => now(),
                'responded_by' => $respondedBy->id,
                'response_notes' => 'Auto-rejected - another bid was accepted',
            ]);

        $this->update([
            'bid_status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
            'responded_by' => $respondedBy->id,
            'response_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Reject the bid
     */
    public function reject(Employee $respondedBy, string $notes = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'bid_status' => self::STATUS_REJECTED,
            'responded_at' => now(),
            'responded_by' => $respondedBy->id,
            'response_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Withdraw the bid
     */
    public function withdraw(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'bid_status' => self::STATUS_WITHDRAWN,
            'responded_at' => now(),
        ]);

        return true;
    }

    /**
     * Check if bid can be accepted
     */
    public function canBeAccepted(): bool
    {
        return $this->isPending() && $this->replacementRequest->canBeFulfilled();
    }

    /**
     * Check if bid can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    /**
     * Check if bid can be withdrawn
     */
    public function canBeWithdrawn(): bool
    {
        return $this->isPending();
    }

    /**
     * Get bid age in hours
     */
    public function getBidAgeAttribute(): float
    {
        return $this->bid_at->diffInHours(now());
    }

    /**
     * Get response time in hours
     */
    public function getResponseTimeAttribute(): ?float
    {
        if (!$this->responded_at) {
            return null;
        }

        return $this->bid_at->diffInHours($this->responded_at);
    }

    /**
     * Get formatted bid amount
     */
    public function getFormattedBidAmountAttribute(): ?string
    {
        if (!$this->bid_amount) {
            return null;
        }

        return '$' . number_format($this->bid_amount, 2);
    }

    /**
     * Check if bid has financial incentive
     */
    public function hasIncentive(): bool
    {
        return !is_null($this->bid_amount) && $this->bid_amount > 0;
    }

    /**
     * Get bid priority score (for ranking)
     */
    public function getPriorityScoreAttribute(): float
    {
        $score = 0;

        // Priority rank (lower is better)
        if ($this->priority_rank) {
            $score += (10 - $this->priority_rank) * 10;
        }

        // Bid amount (higher is better)
        if ($this->bid_amount) {
            $score += $this->bid_amount;
        }

        // Earlier bids get slight preference
        $hoursOld = $this->bid_age;
        $score += max(0, 24 - $hoursOld) * 0.1;

        return $score;
    }

    /**
     * Set priority rank based on score
     */
    public function calculatePriorityRank(): void
    {
        $rank = self::where('replacement_request_id', $this->replacement_request_id)
                   ->where('bid_status', self::STATUS_PENDING)
                   ->where(function ($query) {
                       $query->where('bid_amount', '>', $this->bid_amount ?: 0)
                             ->orWhere(function ($query) {
                                 $query->where('bid_amount', $this->bid_amount ?: 0)
                                       ->where('bid_at', '<', $this->bid_at);
                             });
                   })
                   ->count() + 1;

        $this->update(['priority_rank' => $rank]);
    }

    /**
     * Get shift details for the bid
     */
    public function getShiftDetailsAttribute(): array
    {
        $shift = $this->shift;
        
        return [
            'date' => $shift->date_start->format('Y-m-d'),
            'start_time' => $shift->date_start->format('H:i'),
            'end_time' => $shift->date_end->format('H:i'),
            'duration' => $shift->total_hours,
            'location' => $shift->location?->name,
        ];
    }
}