<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shift_id',
        'employee_id',
        'status',
        'assignment_type',
        'priority',
        'replaced_by_assignment_id',
        'replaces_assignment_id',
        'assigned_at',
        'responded_at',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'responded_at' => 'datetime',
        'completed_at' => 'datetime',
        'priority' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'assigned',
        'assignment_type' => 'primary',
        'priority' => 1,
        'assigned_at' => 'now',
    ];

    /**
     * The possible assignment statuses
     */
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';

    public const STATUSES = [
        self::STATUS_ASSIGNED,
        self::STATUS_ACCEPTED,
        self::STATUS_DECLINED,
        self::STATUS_COMPLETED,
        self::STATUS_NO_SHOW,
    ];

    /**
     * The possible assignment types
     */
    public const TYPE_PRIMARY = 'primary';
    public const TYPE_BACKUP = 'backup';
    public const TYPE_REPLACEMENT = 'replacement';

    public const TYPES = [
        self::TYPE_PRIMARY,
        self::TYPE_BACKUP,
        self::TYPE_REPLACEMENT,
    ];

    /**
     * Get the shift this assignment belongs to
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the employee assigned to this shift
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the assignment that replaced this one
     */
    public function replacedByAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class, 'replaced_by_assignment_id');
    }

    /**
     * Get the assignment this one replaces
     */
    public function replacesAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class, 'replaces_assignment_id');
    }

    /**
     * Get replacement requests for this assignment
     */
    public function replacementRequests(): HasMany
    {
        return $this->hasMany(ReplacementRequest::class);
    }

    /**
     * Get shift swaps where this assignment is the requestor
     */
    public function requestedSwaps(): HasMany
    {
        return $this->hasMany(ShiftSwap::class, 'requestor_assignment_id');
    }

    /**
     * Get shift swaps where this assignment is the target
     */
    public function targetedSwaps(): HasMany
    {
        return $this->hasMany(ShiftSwap::class, 'target_assignment_id');
    }

    /**
     * Get the company through the shift
     */
    public function company(): BelongsTo
    {
        return $this->shift->company();
    }

    /**
     * Scope assignments by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope assignments by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('assignment_type', $type);
    }

    /**
     * Scope primary assignments
     */
    public function scopePrimary($query)
    {
        return $query->where('assignment_type', self::TYPE_PRIMARY);
    }

    /**
     * Scope backup assignments
     */
    public function scopeBackup($query)
    {
        return $query->where('assignment_type', self::TYPE_BACKUP);
    }

    /**
     * Scope replacement assignments
     */
    public function scopeReplacement($query)
    {
        return $query->where('assignment_type', self::TYPE_REPLACEMENT);
    }

    /**
     * Scope assigned assignments
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', self::STATUS_ASSIGNED);
    }

    /**
     * Scope accepted assignments
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope declined assignments
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', self::STATUS_DECLINED);
    }

    /**
     * Scope completed assignments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope assignments for a specific employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope assignments for a date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereHas('shift', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date_start', [$startDate, $endDate]);
        });
    }

    /**
     * Check if assignment is assigned
     */
    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    /**
     * Check if assignment is accepted
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if assignment is declined
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if assignment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if assignment is no show
     */
    public function isNoShow(): bool
    {
        return $this->status === self::STATUS_NO_SHOW;
    }

    /**
     * Check if assignment is primary
     */
    public function isPrimary(): bool
    {
        return $this->assignment_type === self::TYPE_PRIMARY;
    }

    /**
     * Check if assignment is backup
     */
    public function isBackup(): bool
    {
        return $this->assignment_type === self::TYPE_BACKUP;
    }

    /**
     * Check if assignment is replacement
     */
    public function isReplacement(): bool
    {
        return $this->assignment_type === self::TYPE_REPLACEMENT;
    }

    /**
     * Accept the assignment
     */
    public function accept(): bool
    {
        if (!$this->isAssigned()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        return true;
    }

    /**
     * Decline the assignment
     */
    public function decline(): bool
    {
        if (!$this->isAssigned()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_DECLINED,
            'responded_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark assignment as completed
     */
    public function complete(): bool
    {
        if (!in_array($this->status, [self::STATUS_ASSIGNED, self::STATUS_ACCEPTED])) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark assignment as no show
     */
    public function markAsNoShow(): bool
    {
        if (!in_array($this->status, [self::STATUS_ASSIGNED, self::STATUS_ACCEPTED])) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_NO_SHOW,
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Check if assignment can be responded to
     */
    public function canBeResponded(): bool
    {
        return $this->isAssigned();
    }

    /**
     * Check if assignment can be completed
     */
    public function canBeCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_ASSIGNED, self::STATUS_ACCEPTED]);
    }

    /**
     * Get response time in hours
     */
    public function getResponseTimeAttribute(): ?float
    {
        if (!$this->responded_at) {
            return null;
        }

        return $this->assigned_at->diffInHours($this->responded_at);
    }

    /**
     * Get completion time in hours
     */
    public function getCompletionTimeAttribute(): ?float
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->assigned_at->diffInHours($this->completed_at);
    }
}