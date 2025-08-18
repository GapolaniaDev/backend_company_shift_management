<?php

namespace App\GraphQL\Resolvers;

use App\Models\Shift;

class EmployeeResolver
{
    /**
     * Resolver for Employee.shifts with optional filtering
     */
    public function shifts($root, array $args)
    {
        $filter = $args['filter'] ?? [];

        // Start with the employee's shifts
        $query = $root->shifts()->getQuery();

        // Apply the filter using the existing scope
        $query = $query->filterShifts($filter);

        // If no specific employee IDs are provided in the filter, 
        // ensure we only get shifts for this employee
        if (!isset($filter['employeeIds'])) {
            $query->where('employee_id', $root->id);
        }

        // Order by date_start for consistent results
        $query->orderBy('date_start', 'asc');

        return $query->get();
    }
}