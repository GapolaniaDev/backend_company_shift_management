<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class ShiftTemplate extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'shift_type_id',
        'location_id',
        'name',
        'description',
        'rrule',
        'start_time',
        'end_time',
        'hours',
        'capacity',
        'auto_assign',
        'effective_from',
        'effective_to',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'hours' => 'decimal:2',
        'capacity' => 'integer',
        'auto_assign' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'capacity' => 1,
        'auto_assign' => false,
        'is_active' => true,
    ];

    /**
     * Get the company that owns the shift template
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the shift type for this template
     */
    public function shiftType(): BelongsTo
    {
        return $this->belongsTo(ShiftType::class);
    }

    /**
     * Get the location for this template
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get shifts generated from this template
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Get template exceptions for this template
     */
    public function templateExceptions(): HasMany
    {
        return $this->hasMany(TemplateException::class);
    }

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get templates effective on a given date
     */
    public function scopeEffectiveOn($query, $date = null)
    {
        $date = $date ?? today();
        
        return $query->where('effective_from', '<=', $date)
                    ->where(function ($query) use ($date) {
                        $query->whereNull('effective_to')
                              ->orWhere('effective_to', '>=', $date);
                    });
    }

    /**
     * Scope to get templates for a specific shift type
     */
    public function scopeForShiftType($query, $shiftTypeId)
    {
        return $query->where('shift_type_id', $shiftTypeId);
    }

    /**
     * Scope to get templates for a specific location
     */
    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Check if template is currently effective
     */
    public function isEffective($date = null): bool
    {
        $date = $date ?? today();
        
        if ($this->effective_from > $date) {
            return false;
        }
        
        if ($this->effective_to && $this->effective_to < $date) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if template is expired
     */
    public function isExpired($date = null): bool
    {
        $date = $date ?? today();
        
        return $this->effective_to && $this->effective_to < $date;
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->hours);
        $minutes = ($this->hours - $hours) * 60;
        
        if ($minutes > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$hours}h";
    }

    /**
     * Get time range as formatted string
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time . ' - ' . $this->end_time;
    }

    /**
     * Parse RRULE to get next occurrence
     */
    public function getNextOccurrence($fromDate = null): ?Carbon
    {
        // This would require a library like recurr/recurr to parse RRULE
        // For now, returning null - implement based on RRULE parsing needs
        return null;
    }

    /**
     * Get all occurrences between two dates
     */
    public function getOccurrencesBetween(Carbon $startDate, Carbon $endDate): array
    {
        // This would require a library like recurr/recurr to parse RRULE
        // For now, returning empty array - implement based on RRULE parsing needs
        return [];
    }

    /**
     * Check if template has capacity for more assignments
     */
    public function hasCapacity($date = null): bool
    {
        if (!$date) {
            return true; // Cannot check capacity without specific date
        }

        $assignedCount = $this->shifts()
            ->whereDate('date_start', $date)
            ->sum('capacity'); // Assuming shifts table has capacity field

        return $assignedCount < $this->capacity;
    }
}