<?php

namespace App\GraphQL\Resolvers;

use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;

class LocationResolver
{
    /**
     * Resolver for Query.locations with optional ids filter and pagination
     */
    public function locations($_, array $args)
    {
        $query = Location::query();

        // Apply filters
        if (isset($args['ids']) && is_array($args['ids']) && count($args['ids'])) {
            $query->whereIn('id', $args['ids']);
        }
        
        if (isset($args['name'])) {
            $query->where('name', 'like', '%' . $args['name'] . '%');
        }
        
        if (isset($args['is_active'])) {
            $query->where('is_active', (bool) $args['is_active']);
        }

        // Apply ordering
        if (isset($args['orderBy']) && is_array($args['orderBy'])) {
            foreach ($args['orderBy'] as $orderBy) {
                $query->orderBy($orderBy['column'], $orderBy['order']);
            }
        }

        // Paginate results
        $first = $args['first'] ?? 25;
        $page = $args['page'] ?? 1;

        return $query->paginate($first, ['*'], 'page', $page);
    }

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