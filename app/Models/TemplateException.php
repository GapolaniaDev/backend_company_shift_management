<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateException extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_template_id',
        'exception_date',
        'exception_type',
        'start_time',
        'end_time',
        'hours',
        'capacity',
        'location_id',
        'reason',
        'notes',
    ];

    protected $casts = [
        'exception_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'hours' => 'decimal:2',
        'capacity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The possible exception types
     */
    public const EXCEPTION_TYPE_CANCEL = 'cancel';
    public const EXCEPTION_TYPE_MODIFY = 'modify';
    public const EXCEPTION_TYPE_ADD = 'add';

    public const EXCEPTION_TYPES = [
        self::EXCEPTION_TYPE_CANCEL,
        self::EXCEPTION_TYPE_MODIFY,
        self::EXCEPTION_TYPE_ADD,
    ];

    /**
     * Get the shift template that owns this exception
     */
    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    /**
     * Get the location for this exception (if different from template)
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the company through the shift template
     */
    public function company(): BelongsTo
    {
        return $this->shiftTemplate->company();
    }

    /**
     * Scope to get exceptions for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('exception_date', $date);
    }

    /**
     * Scope to get exceptions between dates
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('exception_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get exceptions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('exception_type', $type);
    }

    /**
     * Scope to get cancelled exceptions
     */
    public function scopeCancelled($query)
    {
        return $query->where('exception_type', self::EXCEPTION_TYPE_CANCEL);
    }

    /**
     * Scope to get modified exceptions
     */
    public function scopeModified($query)
    {
        return $query->where('exception_type', self::EXCEPTION_TYPE_MODIFY);
    }

    /**
     * Scope to get additional exceptions
     */
    public function scopeAdditional($query)
    {
        return $query->where('exception_type', self::EXCEPTION_TYPE_ADD);
    }

    /**
     * Check if this exception cancels the template occurrence
     */
    public function isCancellation(): bool
    {
        return $this->exception_type === self::EXCEPTION_TYPE_CANCEL;
    }

    /**
     * Check if this exception modifies the template occurrence
     */
    public function isModification(): bool
    {
        return $this->exception_type === self::EXCEPTION_TYPE_MODIFY;
    }

    /**
     * Check if this exception adds an additional occurrence
     */
    public function isAddition(): bool
    {
        return $this->exception_type === self::EXCEPTION_TYPE_ADD;
    }

    /**
     * Get formatted time range if applicable
     */
    public function getTimeRangeAttribute(): ?string
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        return $this->start_time . ' - ' . $this->end_time;
    }

    /**
     * Get formatted duration if applicable
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->hours) {
            return null;
        }

        $hours = floor($this->hours);
        $minutes = ($this->hours - $hours) * 60;
        
        if ($minutes > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$hours}h";
    }

    /**
     * Get exception summary
     */
    public function getSummaryAttribute(): string
    {
        $type = ucfirst($this->exception_type);
        $date = $this->exception_date->format('Y-m-d');
        
        switch ($this->exception_type) {
            case self::EXCEPTION_TYPE_CANCEL:
                return "Cancel shift on {$date}";
            
            case self::EXCEPTION_TYPE_MODIFY:
                $time = $this->time_range ? " ({$this->time_range})" : '';
                return "Modify shift on {$date}{$time}";
            
            case self::EXCEPTION_TYPE_ADD:
                $time = $this->time_range ? " ({$this->time_range})" : '';
                return "Add shift on {$date}{$time}";
            
            default:
                return "{$type} on {$date}";
        }
    }

    /**
     * Check if exception affects a specific template
     */
    public static function hasExceptionOn($shiftTemplateId, $date)
    {
        return self::where('shift_template_id', $shiftTemplateId)
                   ->where('exception_date', $date)
                   ->exists();
    }

    /**
     * Get exception for template on specific date
     */
    public static function getExceptionFor($shiftTemplateId, $date)
    {
        return self::where('shift_template_id', $shiftTemplateId)
                   ->where('exception_date', $date)
                   ->first();
    }
}