<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'radius',
        'zoom',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8', 
        'radius' => 'integer',
        'zoom' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'radius' => 100,
        'zoom' => 16,
        'timezone' => 'UTC',
        'is_active' => true,
    ];

    /**
     * Get the company that owns the location
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get shift templates for this location
     */
    public function shiftTemplates(): HasMany
    {
        return $this->hasMany(ShiftTemplate::class);
    }

    /**
     * Get shifts for this location
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Get template exceptions for this location
     */
    public function templateExceptions(): HasMany
    {
        return $this->hasMany(TemplateException::class);
    }

    /**
     * Scope to get only active locations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get locations within a certain radius of coordinates
     */
    public function scopeWithinRadius($query, $latitude, $longitude, $radiusKm = 10)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->selectRaw("
            *,
            ( {$earthRadius} * acos( cos( radians(?) ) * cos( radians( latitude ) )
            * cos( radians( longitude ) - radians(?) )
            + sin( radians(?) ) * sin(radians(latitude)) )
            ) AS distance", [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radiusKm)
            ->orderBy('distance');
    }

    /**
     * Get formatted address attribute
     */
    public function getFormattedAddressAttribute(): ?string
    {
        if (!$this->address) {
            return null;
        }

        return $this->address;
    }

    /**
     * Check if location has coordinates
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get coordinates as array
     */
    public function getCoordinatesAttribute(): ?array
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Scope to filter locations by multiple IDs
     */
    public function scopeWhereIds($query, $ids)
    {
        // Handle various input formats
        if (!$ids) {
            return $query;
        }
        
        // If it's a string, try to handle it gracefully
        if (is_string($ids)) {
            $ids = trim($ids);
            if ($ids === '') {
                return $query;
            }
            // If it's a comma-separated string, convert to array
            if (strpos($ids, ',') !== false) {
                $ids = array_map('trim', explode(',', $ids));
            } else {
                $ids = [$ids];
            }
        }
        
        if (is_array($ids) && count($ids) > 0) {
            // Filter out empty values
            $ids = array_filter($ids, function($id) {
                return $id !== null && $id !== '';
            });
            
            if (count($ids) > 0) {
                return $query->whereIn('id', $ids);
            }
        }
        
        return $query;
    }

    /**
     * Scope to filter locations by name (handles empty strings)
     */
    public function scopeWhereName($query, $name)
    {
        if ($name && trim($name) !== '') {
            return $query->where('name', 'like', '%' . $name . '%');
        }
        
        return $query;
    }
}