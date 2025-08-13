<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduleRun extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'period_name',
        'status',
        'generated_at',
        'published_at',
        'generated_by',
        'published_by',
        'generation_summary',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'generation_summary' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * The possible statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEW,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    /**
     * Get the company that owns the schedule run
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who generated this schedule
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the user who published this schedule
     */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Get shifts generated in this schedule run
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Scope to get runs by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get draft runs
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to get runs in review
     */
    public function scopeInReview($query)
    {
        return $query->where('status', self::STATUS_REVIEW);
    }

    /**
     * Scope to get published runs
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope to get archived runs
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope to get runs for a specific period
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                    ->where('period_end', '<=', $endDate);
    }

    /**
     * Scope to get runs overlapping with a date range
     */
    public function scopeOverlapping($query, $startDate, $endDate)
    {
        return $query->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('period_start', [$startDate, $endDate])
                  ->orWhereBetween('period_end', [$startDate, $endDate])
                  ->orWhere(function ($query) use ($startDate, $endDate) {
                      $query->where('period_start', '<=', $startDate)
                            ->where('period_end', '>=', $endDate);
                  });
        });
    }

    /**
     * Check if run is in draft status
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if run is in review status
     */
    public function isInReview(): bool
    {
        return $this->status === self::STATUS_REVIEW;
    }

    /**
     * Check if run is published
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if run is archived
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Move run to review status
     */
    public function moveToReview(): bool
    {
        if (!$this->isDraft()) {
            return false;
        }

        $this->update(['status' => self::STATUS_REVIEW]);
        return true;
    }

    /**
     * Publish the schedule run
     */
    public function publish(User $publishedBy): bool
    {
        if ($this->isPublished()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => $publishedBy->id,
        ]);

        return true;
    }

    /**
     * Archive the schedule run
     */
    public function archive(): bool
    {
        if (!$this->isPublished()) {
            return false;
        }

        $this->update(['status' => self::STATUS_ARCHIVED]);
        return true;
    }

    /**
     * Get formatted period
     */
    public function getFormattedPeriodAttribute(): string
    {
        return $this->period_start->format('M j') . ' - ' . $this->period_end->format('M j, Y');
    }

    /**
     * Get period duration in days
     */
    public function getPeriodDurationAttribute(): int
    {
        return $this->period_start->diffInDays($this->period_end) + 1;
    }

    /**
     * Get generation summary stats
     */
    public function getGenerationStatsAttribute(): array
    {
        if (!$this->generation_summary) {
            return [];
        }

        return $this->generation_summary;
    }

    /**
     * Get shifts count for this run
     */
    public function getShiftsCountAttribute(): int
    {
        return $this->shifts()->count();
    }

    /**
     * Get assigned shifts count
     */
    public function getAssignedShiftsCountAttribute(): int
    {
        return $this->shifts()->whereNotNull('employee_id')->count();
    }

    /**
     * Get unassigned shifts count
     */
    public function getUnassignedShiftsCountAttribute(): int
    {
        return $this->shifts()->whereNull('employee_id')->count();
    }

    /**
     * Get assignment completion percentage
     */
    public function getAssignmentCompletionAttribute(): float
    {
        $totalShifts = $this->shifts_count;
        
        if ($totalShifts === 0) {
            return 0;
        }

        return round(($this->assigned_shifts_count / $totalShifts) * 100, 2);
    }

    /**
     * Check if run can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REVIEW]);
    }

    /**
     * Check if run can be published
     */
    public function canBePublished(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REVIEW]);
    }
}