<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Shift;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    /**
     * Get shifts by date range with filters and dynamic grouping
     *
     * @param string $startDate Start date in YYYY-MM-DD format
     * @param string $endDate End date in YYYY-MM-DD format
     * @param string $viewType View type: daily, weekly, monthly
     * @param array $userIds List of employee IDs to filter
     * @param array $locationIds List of location IDs to filter
     * @return \Illuminate\Support\Collection List of filtered and grouped shifts
     */
    public function getShiftsByRange(string $startDate, string $endDate, string $viewType = 'daily', array $userIds = [], array $locationIds = [])
    {
        // Convert dates to Carbon objects for easy handling
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Build the base query
        $query = Shift::whereBetween('date_start', [$start, $end]);

        // Apply employee filter (if not empty)
        /*if (!empty($userIds)) {
            $query->whereIn('employee_id', $userIds);
        }*/

        // Apply workplace filter (if not empty)
        /*if (!empty($locationIds)) {
            $query->whereIn('location', $locationIds);
        }*/

        // Execute the query optimizing data selection
        $shifts = $query
            ->select([
                'id',
                'date_start',
                'date_end',
                'date_start_timezone',
                'date_end_timezone',
                'employee_id',
                //'location',
                'shift_type_id',
                'state',
                'total_hours',
                'comments',
                'weekday_code'
            ])
            ->with([
                'employee:id,first_name,last_name,weekly_working_hours', // Relate employee with basic data
                'shiftType:id,name' // Add shift type color if exists
                //'shiftType:id,name,color'
            ])
            ->orderBy('date_start') // Order by start time
            ->get();

        // Add local time information to each shift
        $shifts->transform(function ($shift) {
            // Add local times if they exist
            if ($shift->date_start) {
                $shift->local_date_start = $shift->getLocalStartTime()->toDateTimeString();
            }

            if ($shift->date_end) {
                $shift->local_date_end = $shift->getLocalEndTime()->toDateTimeString();
            }

            return $shift;
        });

        // Group shifts according to the requested view type
        switch ($viewType) {
            case 'monthly':
                return $shifts->groupBy(function ($shift) {
                    return Carbon::parse($shift->date_start)->format('Y-m-d'); // Group by Month (Ex: "2023-11")
                });

            case 'weekly':
                return $shifts->groupBy(function ($shift) {
                    $startOfWeek = Carbon::parse($shift->date_start)->startOfWeek();
                    return $startOfWeek->format('Y-m-d'); // Group by week (date at the start of the week)
                });

            case 'daily':
            default:
                return $shifts->groupBy(function ($shift) {
                    return Carbon::parse($shift->date_start)->format('Y-m-d'); // Group by Day (Ex: "2023-11-07")
                });
        }
    }

    /**
     * Checks if a shift overlaps with existing shifts for the employee
     *
     * @param int $employeeId Employee ID
     * @param Carbon $dateStart Start date and time
     * @param Carbon $dateEnd End date and time
     * @param int|null $excludeShiftId Shift ID to exclude (for editing)
     * @return bool True if there is overlap, False if there is not
     */
    public function hasOverlappingShifts(int $employeeId, Carbon $dateStart, Carbon $dateEnd, ?int $excludeShiftId = null): bool
    {
        $query = Shift::where('employee_id', $employeeId)
            ->where(function (Builder $query) use ($dateStart, $dateEnd) {
                // Overlap: new shift starts during an existing shift
                $query->where(function (Builder $q) use ($dateStart, $dateEnd) {
                    $q->where('date_start', '<=', $dateStart)
                      ->where('date_end', '>=', $dateStart);
                })
                // Or new shift ends during an existing shift
                ->orWhere(function (Builder $q) use ($dateStart, $dateEnd) {
                    $q->where('date_start', '<=', $dateEnd)
                      ->where('date_end', '>=', $dateEnd);
                })
                // Or new shift completely covers an existing shift
                ->orWhere(function (Builder $q) use ($dateStart, $dateEnd) {
                    $q->where('date_start', '>=', $dateStart)
                      ->where('date_end', '<=', $dateEnd);
                });
            });

        // If we're editing an existing shift, exclude it from validation
        if ($excludeShiftId) {
            $query->where('id', '!=', $excludeShiftId);
        }

        return $query->exists();
    }

    /**
     * Calculates the total working hours for an employee in a specific week
     *
     * @param int $employeeId Employee ID
     * @param Carbon $weekStart Week start date
     * @param int|null $excludeShiftId Shift ID to exclude (for editing)
     * @return float Total hours worked in the week
     */
    public function getWeeklyHoursTotal(int $employeeId, Carbon $weekStart, ?int $excludeShiftId = null): float
    {
        // Get the end of the week (6 days after the start)
        $weekEnd = (clone $weekStart)->addDays(6)->endOfDay();

        $query = Shift::where('employee_id', $employeeId)
            ->where(function (Builder $query) use ($weekStart, $weekEnd) {
                // Shifts that start or end within the week
                $query->whereBetween('date_start', [$weekStart, $weekEnd])
                    ->orWhereBetween('date_end', [$weekStart, $weekEnd])
                    // Or shifts that span the entire week
                    ->orWhere(function (Builder $q) use ($weekStart, $weekEnd) {
                        $q->where('date_start', '<=', $weekStart)
                          ->where('date_end', '>=', $weekEnd);
                    });
            });

        // If we're editing an existing shift, exclude it from the calculation
        if ($excludeShiftId) {
            $query->where('id', '!=', $excludeShiftId);
        }

        return $query->sum('total_hours');
    }

    /**
     * Checks if a new shift would exceed the employee's weekly hour limit
     *
     * @param int $employeeId Employee ID
     * @param Carbon $dateStart Shift start date and time
     * @param Carbon $dateEnd Shift end date and time
     * @param float $shiftHours Total hours of the new shift
     * @param int|null $excludeShiftId Shift ID to exclude (for editing)
     * @return bool True if it exceeds the limit, False if it's within the limit
     */
    public function exceedsWeeklyHoursLimit(int $employeeId, Carbon $dateStart, Carbon $dateEnd, float $shiftHours, ?int $excludeShiftId = null): bool
    {
        // Get the employee's weekly limit
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->weekly_working_hours) {
            return false; // No limit defined, we allow it
        }

        $weeklyLimit = (float) $employee->weekly_working_hours;

        // Get the start of the week for the shift
        $weekStart = (clone $dateStart)->startOfWeek();

        // If the shift spans more than one week, we need to check each week
        $currentWeekStart = clone $weekStart;
        $shiftEndWeek = (clone $dateEnd)->startOfWeek();

        // For each week that the shift spans
        while ($currentWeekStart->lte($shiftEndWeek)) {
            // Calculate current hours for this week
            $currentWeekHours = $this->getWeeklyHoursTotal($employeeId, $currentWeekStart, $excludeShiftId);

            // For shifts spanning multiple weeks, calculate the proportion for each week
            $weekProportion = $this->calculateWeekProportion($dateStart, $dateEnd, $currentWeekStart);
            $newWeeklyHours = $currentWeekHours + ($shiftHours * $weekProportion);

            // Check if it exceeds the limit
            if ($newWeeklyHours > $weeklyLimit) {
                return true; // Exceeds the limit
            }

            // Move to the next week
            $currentWeekStart->addWeek();
        }

        return false; // Does not exceed the limit in any week
    }

    /**
     * Calculates the proportion of a shift that falls within a specific week
     *
     * @param Carbon $shiftStart Shift start
     * @param Carbon $shiftEnd Shift end
     * @param Carbon $weekStart Week start
     * @return float Proportion (0.0 - 1.0)
     */
    private function calculateWeekProportion(Carbon $shiftStart, Carbon $shiftEnd, Carbon $weekStart): float
    {
        $weekEnd = (clone $weekStart)->addDays(6)->endOfDay();
        $totalShiftHours = $shiftEnd->diffInSeconds($shiftStart) / 3600;

        if ($totalShiftHours <= 0) {
            return 0;
        }

        // Calculate effective limits for the intersection of the shift with the week
        $effectiveStart = max($shiftStart->timestamp, $weekStart->timestamp);
        $effectiveEnd = min($shiftEnd->timestamp, $weekEnd->timestamp);

        // If there is no intersection
        if ($effectiveEnd <= $effectiveStart) {
            return 0;
        }

        // Calculate the proportion of hours that fall in this week
        $weekHours = ($effectiveEnd - $effectiveStart) / 3600;
        return min(1, max(0, $weekHours / $totalShiftHours));
    }

    /**
     * Validates a new shift or an update to an existing one
     *
     * @param array $data Shift data
     * @param int|null $shiftId Shift ID (null for new shifts)
     * @return array Array with validation result [success, message]
     */
    public function validateShift(array $data, ?int $shiftId = null): array
    {
        $dateStart = Carbon::parse($data['date_start']);
        $dateEnd = Carbon::parse($data['date_end']);
        $employeeId = $data['employee_id'];
        $totalHours = isset($data['total_hours']) ? (float) $data['total_hours'] : $dateEnd->diffInMinutes($dateStart) / 60;

        // Validate that end date is after start date
        if ($dateEnd <= $dateStart) {
            return [false, 'The end date must be after the start date.'];
        }

        // Validate that there is no overlap with other employee shifts
        if ($this->hasOverlappingShifts($employeeId, $dateStart, $dateEnd, $shiftId)) {
            return [false, 'The shift overlaps with other shifts assigned to the employee.'];
        }

        // Validate that it does not exceed the weekly hour limit
        if ($this->exceedsWeeklyHoursLimit($employeeId, $dateStart, $dateEnd, $totalHours, $shiftId)) {
            $employee = Employee::find($employeeId);
            return [false, "The shift exceeds the employee's weekly limit of {$employee->weekly_working_hours} hours."];
        }

        return [true, ''];
    }

    /**
     * Creates a new shift with validations
     *
     * @param array $data Shift data
     * @return array [success, data|message]
     */
    public function createShift(array $data): array
    {
        // Specific validations for creation
        [$valid, $message] = $this->validateShift($data);
        if (!$valid) {
            return [false, $message];
        }

        try {
            DB::beginTransaction();

            // Calculate total_hours if not provided
            if (!isset($data['total_hours'])) {
                $dateStart = Carbon::parse($data['date_start']);
                $dateEnd = Carbon::parse($data['date_end']);
                $data['total_hours'] = round($dateEnd->diffInMinutes($dateStart) / 60, 2);
            }

            // Calculate weekday_code if not provided
            if (!isset($data['weekday_code'])) {
                $data['weekday_code'] = Carbon::parse($data['date_start'])->dayOfWeek;
            }

            $shift = Shift::create($data);

            DB::commit();
            return [true, $shift];
        } catch (\Exception $e) {
            DB::rollBack();
            return [false, 'Error creating shift: ' . $e->getMessage()];
        }
    }

    /**
     * Updates an existing shift with validations
     *
     * @param int $shiftId Shift ID
     * @param array $data Data to update
     * @return array [success, data|message]
     */
    public function updateShift(int $shiftId, array $data): array
    {
        $shift = Shift::findOrFail($shiftId);

        // Combine existing data with new data for complete validation
        $validationData = array_merge($shift->toArray(), $data);

        // Specific validations for update
        [$valid, $message] = $this->validateShift($validationData, $shiftId);
        if (!$valid) {
            return [false, $message];
        }

        try {
            DB::beginTransaction();

            // Recalculate total_hours if dates were modified
            if (isset($data['date_start']) || isset($data['date_end'])) {
                $dateStart = isset($data['date_start']) ? Carbon::parse($data['date_start']) : $shift->date_start;
                $dateEnd = isset($data['date_end']) ? Carbon::parse($data['date_end']) : $shift->date_end;
                $data['total_hours'] = round($dateEnd->diffInMinutes($dateStart) / 60, 2);

                // Update weekday_code if start date changed
                if (isset($data['date_start'])) {
                    $data['weekday_code'] = Carbon::parse($data['date_start'])->dayOfWeek;
                }
            }

            $shift->update($data);

            DB::commit();
            return [true, $shift];
        } catch (\Exception $e) {
            DB::rollBack();
            return [false, 'Error updating shift: ' . $e->getMessage()];
        }
    }
}
