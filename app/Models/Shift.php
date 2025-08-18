<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Shift",
 *     title="Shift",
 *     description="Work shift with schedule, employee assignment, and clock in/out data",
 *
 *     @OA\Property(property="id", type="integer", format="int64", example=1, description="Unique identifier"),
 *     @OA\Property(property="shift_type_id", type="integer", example=2, description="Associated shift type ID"),
 *     @OA\Property(property="employee_id", type="integer", example=5, description="Assigned employee ID"),
 *     @OA\Property(property="date_start", type="string", format="date-time", example="2025-03-10T09:00:00Z", description="Shift start date and time (UTC)"),
 *     @OA\Property(property="date_end", type="string", format="date-time", example="2025-03-10T17:00:00Z", description="Shift end date and time (UTC)"),
 *     @OA\Property(property="date_start_timezone", type="string", nullable=true, example="America/New_York", description="Timezone for shift start calculated from coordinates"),
 *     @OA\Property(property="date_end_timezone", type="string", nullable=true, example="America/New_York", description="Timezone for shift end calculated from coordinates"),
 *     @OA\Property(property="total_hours", type="number", format="float", example=8.5, description="Total scheduled hours"),
 *     @OA\Property(property="weekday_code", type="integer", example=1, description="Day of week (0=Sunday, 6=Saturday)"),
 *     @OA\Property(property="comments", type="string", nullable=true, example="Cover for John", description="Additional shift notes"),
 *     @OA\Property(property="replacement_id", type="integer", nullable=true, example=7, description="Replacement employee ID if applicable"),
 *     @OA\Property(property="location", type="string", nullable=true, example="Main Branch", description="Work location"),
 *     @OA\Property(property="location_lat", type="number", format="float", nullable=true, example=37.7749, description="Location latitude"),
 *     @OA\Property(property="location_lng", type="number", format="float", nullable=true, example=-122.4194, description="Location longitude"),
 *     @OA\Property(property="radius", type="integer", nullable=true, example=100, description="Allowable radius in meters for clock in/out"),
 *     @OA\Property(property="zoom", type="integer", nullable=true, example=16, description="Map zoom level for location"),
 *     @OA\Property(property="clock_on_time", type="string", format="date-time", nullable=true, description="Actual clock-in timestamp"),
 *     @OA\Property(property="clock_off_time", type="string", format="date-time", nullable=true, description="Actual clock-out timestamp"),
 *     @OA\Property(property="clock_on_lat", type="number", format="float", nullable=true, example=37.775, description="Clock-in latitude"),
 *     @OA\Property(property="clock_on_lng", type="number", format="float", nullable=true, example=-122.419, description="Clock-in longitude"),
 *     @OA\Property(property="clock_off_lat", type="number", format="float", nullable=true, example=37.775, description="Clock-out latitude"),
 *     @OA\Property(property="clock_off_lng", type="number", format="float", nullable=true, example=-122.419, description="Clock-out longitude"),
 *     @OA\Property(property="timezone_start", type="string", nullable=true, example="America/New_York", description="Timezone where the clock-on occurred"),
 *     @OA\Property(property="timezone_end", type="string", nullable=true, example="America/Los_Angeles", description="Timezone where the clock-off occurred"),
 *     @OA\Property(property="state", type="integer", enum={0, 1, 2}, example=0, description="Shift state: 0=not_started, 1=started, 2=finished"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when record was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when record was last updated"),
 *     @OA\Property(
 *         property="employee",
 *         ref="#/components/schemas/Employee",
 *         description="Assigned employee details"
 *     ),
 *     @OA\Property(
 *         property="shift_type",
 *         ref="#/components/schemas/ShiftType",
 *         description="Shift type details"
 *     ),
 *     @OA\Property(
 *         property="replacement",
 *         ref="#/components/schemas/Employee",
 *         nullable=true,
 *         description="Replacement employee details if applicable"
 *     ),
 *     @OA\Property(
 *         property="image_profile",
 *         type="object",
 *         description="Employee profile image data generated for the UI",
 *         @OA\Property(property="text_profile", type="string", example="JD"),
 *         @OA\Property(property="text_color", type="string", example="1D5A73"),
 *         @OA\Property(property="background_color", type="string", example="E6F1F5")
 *     )
 * )
 */
class Shift extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * State constants for shifts
     */
    const STATE_NOT_STARTED = 0;

    const STATE_STARTED = 1;

    const STATE_FINISHED = 2;

    protected $fillable = [
        'shift_type_id',
        'employee_id',
        'location_id',
        'date_start',
        'date_end',
        'date_start_timezone',
        'date_end_timezone',
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
        'timezone_start',
        'timezone_end',
        'radius',
        'zoom',
        'state',
        'company_id',
    ];

    protected $casts = [
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'clock_on_time' => 'datetime',
        'clock_off_time' => 'datetime',
        'total_hours' => 'decimal:2',
        'state' => 'integer',
    ];

    /**
     * Bootstrap any model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-update state based on clock_on_time and clock_off_time
        static::saving(function ($shift) {
            // If clock_off_time is set, set state to FINISHED
            if ($shift->clock_off_time) {
                $shift->state = self::STATE_FINISHED;
            }
            // If clock_on_time is set but clock_off_time is not, set state to STARTED
            elseif ($shift->clock_on_time) {
                $shift->state = self::STATE_STARTED;
            }
            // If neither is set, set state to NOT_STARTED
            else {
                $shift->state = self::STATE_NOT_STARTED;
            }
        });
    }

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
     * Get the shift template this shift was generated from
     */
    public function shiftTemplate()
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    /**
     * Get the schedule run this shift belongs to
     */
    public function scheduleRun()
    {
        return $this->belongsTo(ScheduleRun::class);
    }

    /**
     * Get the location for this shift
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get shift assignments for this shift
     */
    public function shiftAssignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    /**
     * Get primary assignment for this shift
     */
    public function primaryAssignment()
    {
        return $this->hasOne(ShiftAssignment::class)->where('assignment_type', 'primary');
    }

    /**
     * Get backup assignments for this shift
     */
    public function backupAssignments()
    {
        return $this->hasMany(ShiftAssignment::class)->where('assignment_type', 'backup');
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

        // Validate replacement_id belongs to same company
        if (isset($attributes['replacement_id']) && $attributes['replacement_id']) {
            $replacementEmployee = Employee::find($attributes['replacement_id']);
            if (!$replacementEmployee) {
                throw new \Exception('Replacement employee not found.');
            }
            
            $currentCompanyId = $this->company_id ?? app('currentCompanyId');
            if ($replacementEmployee->company_id !== $currentCompanyId) {
                throw new \Exception('Replacement employee must belong to the same company.');
            }
        }

        return true;
    }

    /**
     * Check if the user is within the radius for a clock operation
     */
    public function isWithinRadius($userLat, $userLng)
    {
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

    /**
     * Get the clock-on time in the original timezone
     *
     * @return \Carbon\Carbon|null
     */
    public function getLocalClockOnTime()
    {
        if (! $this->clock_on_time) {
            return null;
        }

        $timezone = $this->timezone_start ?: 'UTC';

        return $this->clock_on_time->copy()->setTimezone($timezone);
    }

    /**
     * Get the clock-off time in the original timezone
     *
     * @return \Carbon\Carbon|null
     */
    public function getLocalClockOffTime()
    {
        if (! $this->clock_off_time) {
            return null;
        }

        $timezone = $this->timezone_end ?: 'UTC';

        return $this->clock_off_time->copy()->setTimezone($timezone);
    }

    /**
     * Get the shift start time in the saved timezone
     *
     * @return \Carbon\Carbon|null
     */
    public function getLocalStartTime()
    {
        if (! $this->date_start) {
            return null;
        }

        $timezone = $this->date_start_timezone ?: 'UTC';

        return $this->date_start->copy()->setTimezone($timezone);
    }

    /**
     * Get the shift end time in the saved timezone
     *
     * @return \Carbon\Carbon|null
     */
    public function getLocalEndTime()
    {
        if (! $this->date_end) {
            return null;
        }

        $timezone = $this->date_end_timezone ?: 'UTC';

        return $this->date_end->copy()->setTimezone($timezone);
    }

    /**
     * Scope a query to filter shifts by various criteria
     */
    public function scopeFilterShifts($query, $filter)
    {
        if (! $filter) {
            return $query;
        }

        // Filter by shift status
        if (isset($filter['status'])) {
            $query->where('shift_status', $filter['status']);
        }

        // Filter by date range
        if (isset($filter['dateRange'])) {
            $dateRange = $filter['dateRange'];
            if (isset($dateRange['from'])) {
                $query->where('date_start', '>=', $dateRange['from']);
            }
            if (isset($dateRange['to'])) {
                $query->where('date_end', '<=', $dateRange['to']);
            }
        }

        // Filter by single employee (backward compatibility)
        if (isset($filter['employeeId'])) {
            $query->where('employee_id', $filter['employeeId']);
        }

        // Filter by multiple employees
        if (isset($filter['employeeIds']) && is_array($filter['employeeIds']) && count($filter['employeeIds'])) {
            $query->whereIn('employee_id', $filter['employeeIds']);
        }

        // Filter by single shift type (backward compatibility)
        if (isset($filter['shiftTypeId'])) {
            $query->where('shift_type_id', $filter['shiftTypeId']);
        }

        // Filter by multiple shift types
        if (isset($filter['shiftTypeIds']) && is_array($filter['shiftTypeIds']) && count($filter['shiftTypeIds'])) {
            $query->whereIn('shift_type_id', $filter['shiftTypeIds']);
        }

        // Filter by multiple locations
        if (isset($filter['locationIds']) && is_array($filter['locationIds']) && count($filter['locationIds'])) {
            $query->whereIn('location_id', $filter['locationIds']);
        }

        return $query;
    }
}
