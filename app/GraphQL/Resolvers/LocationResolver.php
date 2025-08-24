<?php

namespace App\GraphQL\Resolvers;

use App\Models\Employee;

class LocationResolver
{
    /**
     * Resolver for Location.employees with filtering by shifts
     */
    public function employees($root, array $args)
    {
        $employeeIds = $args['employee_ids'] ?? null;
        $shiftTypeIds = $args['shift_type_ids'] ?? null;
        $startDate = $args['start_date'] ?? null;
        $endDate = $args['end_date'] ?? null;

        // Base query to get employees who have shifts at this location
        $query = Employee::query()
            ->select('employees.*')
            ->join('shifts', 'shifts.employee_id', '=', 'employees.id')
            ->where('shifts.location_id', $root->id)
            ->distinct('employees.id');

        // Apply employee ID filter
        if ($employeeIds && is_array($employeeIds) && count($employeeIds)) {
            $query->whereIn('employees.id', $employeeIds);
        }

        // Apply shift type filter
        if ($shiftTypeIds && is_array($shiftTypeIds) && count($shiftTypeIds)) {
            $query->whereIn('shifts.shift_type_id', $shiftTypeIds);
        }

        // Apply date range filter
        if ($startDate && $endDate) {
            $query->whereBetween('shifts.date_start', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('shifts.date_start', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('shifts.date_start', '<=', $endDate);
        }

        return $query->get();
    }
}