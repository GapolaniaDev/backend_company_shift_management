<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReplacementRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shift_assignment_id',
        'requested_by',
        'request_type',
        'status',
        'urgency',
        'reason',
        'needed_by',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'fulfilled_by_assignment_id',
        'fulfilled_at',
    ];

    protected $casts = [
        'needed_by' => 'datetime',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'urgency' => 'medium',
        'requested_at' => 'now',
    ];

    /**
     * The possible request types
     */
    public const TYPE_FIND_REPLACEMENT = 'find_replacement';
    public const TYPE_CALL_OUT = 'call_out';
    public const TYPE_SWAP_REQUEST = 'swap_request';

    public const TYPES = [
        self::TYPE_FIND_REPLACEMENT,
        self::TYPE_CALL_OUT,
        self::TYPE_SWAP_REQUEST,
    ];

    /**
     * The possible statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_FULFILLED,
        self::STATUS_CANCELLED,
    ];

    /**
     * The possible urgency levels
     */
    public const URGENCY_LOW = 'low';
    public const URGENCY_MEDIUM = 'medium';
    public const URGENCY_HIGH = 'high';
    public const URGENCY_EMERGENCY = 'emergency';

    public const URGENCIES = [
        self::URGENCY_LOW,
        self::URGENCY_MEDIUM,
        self::URGENCY_HIGH,
        self::URGENCY_EMERGENCY,
    ];

    /**
     * Get the shift assignment this request belongs to
     */
    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    /**
     * Get the employee who requested the replacement
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    /**
     * Get the employee who reviewed the request
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewed_by');
    }

    /**
     * Get the assignment that fulfilled this request
     */
    public function fulfilledByAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class, 'fulfilled_by_assignment_id');
    }

    /**
     * Get replacement bids for this request
     */
    public function replacementBids(): HasMany
    {
        return $this->hasMany(ReplacementBid::class);
    }

    /**
     * Get the shift through the assignment
     */
    public function shift(): BelongsTo
    {
        return $this->shiftAssignment->shift();
    }

    /**
     * Get the company through the shift assignment
     */
    public function company(): BelongsTo
    {
        return $this->shiftAssignment->shift->company();
    }

    /**
     * Scope requests by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope requests by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('request_type', $type);
    }

    /**
     * Scope requests by urgency
     */
    public function scopeByUrgency($query, $urgency)
    {
        return $query->where('urgency', $urgency);
    }

    /**
     * Scope pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope fulfilled requests
     */
    public function scopeFulfilled($query)
    {
        return $query->where('status', self::STATUS_FULFILLED);
    }

    /**
     * Scope urgent requests
     */
    public function scopeUrgent($query)
    {
        return $query->whereIn('urgency', [self::URGENCY_HIGH, self::URGENCY_EMERGENCY]);
    }

    /**
     * Scope requests needed soon
     */
    public function scopeNeededSoon($query, $hours = 24)
    {
        return $query->where('needed_by', '<=', now()->addHours($hours));
    }

    /**
     * Scope requests for a specific employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('requested_by', $employeeId);
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if request is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if request is fulfilled
     */
    public function isFulfilled(): bool
    {
        return $this->status === self::STATUS_FULFILLED;
    }

    /**
     * Check if request is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if request is urgent
     */
    public function isUrgent(): bool
    {
        return in_array($this->urgency, [self::URGENCY_HIGH, self::URGENCY_EMERGENCY]);
    }

    /**
     * Check if request is emergency
     */
    public function isEmergency(): bool
    {
        return $this->urgency === self::URGENCY_EMERGENCY;
    }

    /**
     * Approve the request
     */
    public function approve(Employee $reviewer, string $notes = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Reject the request
     */
    public function reject(Employee $reviewer, string $notes = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Fulfill the request
     */
    public function fulfill(ShiftAssignment $assignment): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_FULFILLED,
            'fulfilled_by_assignment_id' => $assignment->id,
            'fulfilled_at' => now(),
        ]);

        return true;
    }

    /**
     * Cancel the request
     */
    public function cancel(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED])) {
            return false;
        }

        $this->update(['status' => self::STATUS_CANCELLED]);
        return true;
    }

    /**
     * Check if request can be reviewed
     */
    public function canBeReviewed(): bool
    {
        return $this->isPending();
    }

    /**
     * Check if request can be fulfilled
     */
    public function canBeFulfilled(): bool
    {
        return $this->isApproved();
    }

    /**
     * Check if request can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    /**
     * Get time until needed
     */
    public function getTimeUntilNeededAttribute(): ?string
    {
        if (!$this->needed_by) {
            return null;
        }

        $diff = now()->diffInHours($this->needed_by, false);
        
        if ($diff < 0) {
            return 'Overdue by ' . abs($diff) . ' hours';
        }
        
        if ($diff < 24) {
            return $diff . ' hours';
        }
        
        return round($diff / 24, 1) . ' days';
    }

    /**
     * Get urgency color
     */
    public function getUrgencyColorAttribute(): string
    {
        return match ($this->urgency) {
            self::URGENCY_EMERGENCY => 'red',
            self::URGENCY_HIGH => 'orange',
            self::URGENCY_MEDIUM => 'yellow',
            self::URGENCY_LOW => 'green',
            default => 'gray',
        };
    }

    /**
     * Get active bids count
     */
    public function getActiveBidsCountAttribute(): int
    {
        return $this->replacementBids()
                    ->where('bid_status', 'pending')
                    ->count();
    }
}