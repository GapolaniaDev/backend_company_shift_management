<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftSwap extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'requestor_assignment_id',
        'target_assignment_id',
        'swap_type',
        'status',
        'proposed_at',
        'target_responded_at',
        'approved_at',
        'completed_at',
        'approved_by',
        'requires_approval',
        'requestor_message',
        'target_response',
        'approval_notes',
        'compensation_amount',
        'swap_terms',
    ];

    protected $casts = [
        'proposed_at' => 'datetime',
        'target_responded_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'requires_approval' => 'boolean',
        'compensation_amount' => 'decimal:2',
        'swap_terms' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'swap_type' => 'direct',
        'status' => 'proposed',
        'requires_approval' => true,
        'proposed_at' => 'now',
    ];

    /**
     * The possible swap types
     */
    public const TYPE_DIRECT = 'direct';
    public const TYPE_THREE_WAY = 'three_way';
    public const TYPE_MULTIPLE = 'multiple';

    public const TYPES = [
        self::TYPE_DIRECT,
        self::TYPE_THREE_WAY,
        self::TYPE_MULTIPLE,
    ];

    /**
     * The possible statuses
     */
    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PROPOSED,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Get the requestor assignment (who initiated the swap)
     */
    public function requestorAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class, 'requestor_assignment_id');
    }

    /**
     * Get the target assignment (who is being asked to swap)
     */
    public function targetAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class, 'target_assignment_id');
    }

    /**
     * Get the employee who approved the swap
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    /**
     * Get the requestor employee
     */
    public function requestorEmployee(): BelongsTo
    {
        return $this->requestorAssignment->employee();
    }

    /**
     * Get the target employee
     */
    public function targetEmployee(): BelongsTo
    {
        return $this->targetAssignment->employee();
    }

    /**
     * Get the requestor shift
     */
    public function requestorShift(): BelongsTo
    {
        return $this->requestorAssignment->shift();
    }

    /**
     * Get the target shift
     */
    public function targetShift(): BelongsTo
    {
        return $this->targetAssignment->shift();
    }

    /**
     * Get the company through the requestor assignment
     */
    public function company(): BelongsTo
    {
        return $this->requestorAssignment->company();
    }

    /**
     * Scope swaps by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope swaps by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('swap_type', $type);
    }

    /**
     * Scope proposed swaps
     */
    public function scopeProposed($query)
    {
        return $query->where('status', self::STATUS_PROPOSED);
    }

    /**
     * Scope swaps pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    /**
     * Scope approved swaps
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope completed swaps
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope swaps for a specific employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->whereHas('requestorAssignment.employee', function ($query) use ($employeeId) {
            $query->where('id', $employeeId);
        })->orWhereHas('targetAssignment.employee', function ($query) use ($employeeId) {
            $query->where('id', $employeeId);
        });
    }

    /**
     * Scope swaps requiring approval
     */
    public function scopeRequiringApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    /**
     * Check if swap is proposed
     */
    public function isProposed(): bool
    {
        return $this->status === self::STATUS_PROPOSED;
    }

    /**
     * Check if swap is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if swap is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if swap is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if swap is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if swap is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if swap is direct (two-person swap)
     */
    public function isDirect(): bool
    {
        return $this->swap_type === self::TYPE_DIRECT;
    }

    /**
     * Target employee accepts the swap
     */
    public function acceptByTarget(string $response = null): bool
    {
        if (!$this->isProposed()) {
            return false;
        }

        $newStatus = $this->requires_approval ? self::STATUS_PENDING_APPROVAL : self::STATUS_APPROVED;

        $this->update([
            'status' => $newStatus,
            'target_responded_at' => now(),
            'target_response' => $response,
        ]);

        // If no approval required, mark as approved
        if (!$this->requires_approval) {
            $this->update(['approved_at' => now()]);
        }

        return true;
    }

    /**
     * Target employee rejects the swap
     */
    public function rejectByTarget(string $response = null): bool
    {
        if (!$this->isProposed()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'target_responded_at' => now(),
            'target_response' => $response,
        ]);

        return true;
    }

    /**
     * Supervisor/manager approves the swap
     */
    public function approve(Employee $approver, string $notes = null): bool
    {
        if (!$this->isPendingApproval()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approver->id,
            'approval_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Supervisor/manager rejects the swap
     */
    public function rejectByApprover(Employee $approver, string $notes = null): bool
    {
        if (!$this->isPendingApproval()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'approval_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Complete the swap by executing the assignment changes
     */
    public function complete(): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        // Swap the employee assignments
        $requestorEmployeeId = $this->requestorAssignment->employee_id;
        $targetEmployeeId = $this->targetAssignment->employee_id;

        $this->requestorAssignment->update(['employee_id' => $targetEmployeeId]);
        $this->targetAssignment->update(['employee_id' => $requestorEmployeeId]);

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Cancel the swap
     */
    public function cancel(): bool
    {
        if ($this->isCompleted()) {
            return false;
        }

        $this->update(['status' => self::STATUS_CANCELLED]);
        return true;
    }

    /**
     * Check if swap can be responded to by target
     */
    public function canBeRespondedByTarget(): bool
    {
        return $this->isProposed();
    }

    /**
     * Check if swap can be approved by supervisor
     */
    public function canBeApproved(): bool
    {
        return $this->isPendingApproval();
    }

    /**
     * Check if swap can be completed
     */
    public function canBeCompleted(): bool
    {
        return $this->isApproved();
    }

    /**
     * Check if swap can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return !$this->isCompleted();
    }

    /**
     * Get response time in hours (from proposal to target response)
     */
    public function getResponseTimeAttribute(): ?float
    {
        if (!$this->target_responded_at) {
            return null;
        }

        return $this->proposed_at->diffInHours($this->target_responded_at);
    }

    /**
     * Get approval time in hours (from target response to approval)
     */
    public function getApprovalTimeAttribute(): ?float
    {
        if (!$this->approved_at || !$this->target_responded_at) {
            return null;
        }

        return $this->target_responded_at->diffInHours($this->approved_at);
    }

    /**
     * Get completion time in hours (from proposal to completion)
     */
    public function getCompletionTimeAttribute(): ?float
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->proposed_at->diffInHours($this->completed_at);
    }

    /**
     * Get formatted compensation amount
     */
    public function getFormattedCompensationAttribute(): ?string
    {
        if (!$this->compensation_amount) {
            return null;
        }

        return '$' . number_format($this->compensation_amount, 2);
    }

    /**
     * Check if swap has financial compensation
     */
    public function hasCompensation(): bool
    {
        return !is_null($this->compensation_amount) && $this->compensation_amount > 0;
    }

    /**
     * Get swap summary
     */
    public function getSummaryAttribute(): string
    {
        $requestorShift = $this->requestorShift;
        $targetShift = $this->targetShift;

        $requestorDate = $requestorShift->date_start->format('M j');
        $targetDate = $targetShift->date_start->format('M j');

        return "Swap {$requestorDate} ↔ {$targetDate}";
    }

    /**
     * Get shifts being swapped
     */
    public function getSwappedShiftsAttribute(): array
    {
        return [
            'requestor_shift' => [
                'id' => $this->requestorShift->id,
                'date' => $this->requestorShift->date_start->format('Y-m-d'),
                'start_time' => $this->requestorShift->date_start->format('H:i'),
                'end_time' => $this->requestorShift->date_end->format('H:i'),
            ],
            'target_shift' => [
                'id' => $this->targetShift->id,
                'date' => $this->targetShift->date_start->format('Y-m-d'),
                'start_time' => $this->targetShift->date_start->format('H:i'),
                'end_time' => $this->targetShift->date_end->format('H:i'),
            ],
        ];
    }
}