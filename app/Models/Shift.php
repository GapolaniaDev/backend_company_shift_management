<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_type_id',
        'employee_id',
        'date_start',
        'date_end',
        'total_hours',
        'weekday_code',
        'comments',
        'replacement_id',
        'clock_on_lat',
        'clock_on_lng',
        'clock_off_lat',
        'clock_off_lng',
        'clock_on_time',
        'clock_off_time',
        'radius',
        'zoom',
    ];

    protected $casts = [
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'total_hours' => 'decimal:2',
    ];

    public function shiftType()
    {
        return $this->belongsTo(ShiftType::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function replacement()
    {
        return $this->belongsTo(Employee::class, 'replacement_id');
    }

    /**
     * Validate updating the shift.
     */
    public function validateBeforeSave(array $attributes)
    {
        // Ensure "clock_on_time" can't be set if "clock_off_time" is already present
        if (isset($attributes['clock_on_time']) && $this->clock_off_time) {
            throw new \Exception('Cannot set clock on time when clock off time already exists.');
        }

        // Ensure "clock_off_time" is later than "clock_on_time"
        if (
            isset($attributes['clock_off_time']) &&
            $this->clock_on_time &&
            $attributes['clock_off_time'] <= $this->clock_on_time
        ) {
            throw new \Exception('Clock off time must be after clock on time.');
        }

        // Validate radius (must be greater than 0)
        if (isset($attributes['radius']) && $attributes['radius'] <= 0) {
            throw new \Exception('Radius must be greater than 0.');
        }

        // Validate zoom (must be greater or equal to 0)
        if (isset($attributes['zoom']) && $attributes['zoom'] < 0) {
            throw new \Exception('Zoom must be greater or equal to 0.');
        }

        return true;
    }

    /**
     * Check if the user is within the radius for a clock operation
     */
    public function isWithinRadius($userLat, $userLng)
    {
        echo json_encode([$userLat,
            $userLng,
            $this->location_lat,
            $this->location_lng]);
        $distance = $this->calculateDistance(
            $userLat,
            $userLng,
            $this->location_lat,
            $this->location_lng
        );

        return $distance <= $this->radius;
    }

    /**
     * Calculate the Haversine distance between two coordinates (in meters)
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $latDiff = $lat2 - $lat1;
        $lngDiff = $lng2 - $lng1;

        $a = sin($latDiff / 2) ** 2 +
            cos($lat1) * cos($lat2) *
            sin($lngDiff / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

}
