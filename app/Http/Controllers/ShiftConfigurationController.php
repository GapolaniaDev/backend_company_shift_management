<?php

namespace App\Http\Controllers;

use App\Models\ShiftConfiguration;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ShiftConfigurationController extends ApiController
{
    /**
     * Display a listing of all shift configurations.
     */
    public function index(Request $request)
    {
        [$page, $pageSize] = $this->getPageParams($request);
        
        $query = ShiftConfiguration::with(['employee', 'shiftType']);
        
        // Filters
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }
        
        if ($request->has('shift_type_id')) {
            $query->where('shift_type_id', $request->input('shift_type_id'));
        }
        
        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSortFields = ['created_at', 'updated_at'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }
        
        $configurations = $query->paginate($pageSize, ['*'], 'page', $page);
        
        return $this->paginatedResponse($configurations);
    }
    
    /**
     * Get shift configurations for supervisor's team.
     */
    public function teamConfigurations(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return $this->errorResponse('Your user account is not linked to an employee profile.', 400);
        }
        
        [$page, $pageSize] = $this->getPageParams($request);
        
        // Get all supervisees
        $superviseeIds = Employee::where('supervisor_id', $employee->id)
            ->pluck('id')
            ->toArray();
            
        if (empty($superviseeIds)) {
            return $this->successResponse([], 'No team members found.');
        }
        
        $query = ShiftConfiguration::with(['employee', 'shiftType'])
            ->whereIn('employee_id', $superviseeIds);
            
        // Employee filter
        if ($request->has('employee_id') && in_array($request->input('employee_id'), $superviseeIds)) {
            $query->where('employee_id', $request->input('employee_id'));
        }
        
        // Shift type filter
        if ($request->has('shift_type_id')) {
            $query->where('shift_type_id', $request->input('shift_type_id'));
        }
        
        $configurations = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
        
        return $this->paginatedResponse($configurations);
    }
    
    /**
     * Get shift configurations for the current employee.
     */
    public function myConfigurations(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return $this->errorResponse('Your user account is not linked to an employee profile.', 404);
        }
        
        [$page, $pageSize] = $this->getPageParams($request);
        
        $query = ShiftConfiguration::with('shiftType')
            ->where('employee_id', $employee->id);
            
        // Shift type filter
        if ($request->has('shift_type_id')) {
            $query->where('shift_type_id', $request->input('shift_type_id'));
        }
        
        $configurations = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
        
        return $this->paginatedResponse($configurations);
    }

    /**
     * Store a newly created shift configuration.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'shift_type_id' => 'required|exists:shift_types,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }
        
        // Check for duplicate configurations
        $existingConfig = ShiftConfiguration::where('employee_id', $request->input('employee_id'))
            ->where('shift_type_id', $request->input('shift_type_id'))
            ->where(function($query) use ($request) {
                // Handle date range overlap
                if ($request->has('end_date')) {
                    $query->where(function($q) use ($request) {
                        $q->whereBetween('start_date', [$request->input('start_date'), $request->input('end_date')])
                          ->orWhereBetween('end_date', [$request->input('start_date'), $request->input('end_date')])
                          ->orWhere(function($inner) use ($request) {
                              $inner->where('start_date', '<=', $request->input('start_date'))
                                   ->where('end_date', '>=', $request->input('end_date'));
                          });
                    });
                } else {
                    // If no end date, check if the requested start date is within any existing configuration's range
                    $query->where(function($q) use ($request) {
                        $q->where('start_date', '<=', $request->input('start_date'))
                          ->where(function($inner) use ($request) {
                              $inner->whereNull('end_date')
                                   ->orWhere('end_date', '>=', $request->input('start_date'));
                          });
                    });
                }
            })
            ->first();
            
        if ($existingConfig) {
            return $this->errorResponse('A configuration for this employee and shift type already exists in the specified date range.', 422);
        }

        try {
            $configuration = ShiftConfiguration::create([
                'employee_id' => $request->input('employee_id'),
                'shift_type_id' => $request->input('shift_type_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'is_active' => $request->input('is_active', true),
                'priority' => $request->input('priority', 1),
            ]);
            
            return $this->successResponse($configuration, 'Shift configuration created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create shift configuration: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified shift configuration.
     */
    public function show(string $id, Request $request)
    {
        $configuration = ShiftConfiguration::with(['employee', 'shiftType'])->findOrFail($id);
        
        // Role-based access control
        $user = $request->user();
        
        if ($user->isEmployee()) {
            // Employee can only view their own configurations
            $employee = $user->employee;
            if (!$employee || $configuration->employee_id !== $employee->id) {
                return $this->errorResponse('Unauthorized to view this configuration', 403);
            }
        } else if ($user->isSupervisor()) {
            // Supervisor can only view configurations of their supervisees
            $employee = $user->employee;
            if ($employee) {
                $superviseeIds = Employee::where('supervisor_id', $employee->id)->pluck('id')->toArray();
                if (!in_array($configuration->employee_id, $superviseeIds)) {
                    return $this->errorResponse('Unauthorized to view this configuration', 403);
                }
            } else {
                return $this->errorResponse('Supervisor account is not linked to an employee profile.', 400);
            }
        }
        
        return $this->successResponse($configuration);
    }

    /**
     * Update the specified shift configuration.
     */
    public function update(Request $request, string $id)
    {
        $configuration = ShiftConfiguration::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|required|exists:employees,id',
            'shift_type_id' => 'sometimes|required|exists:shift_types,id',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }
        
        // Check for duplicate configurations (excluding this one)
        if ($request->has('employee_id') || $request->has('shift_type_id') || $request->has('start_date') || $request->has('end_date')) {
            $employeeId = $request->input('employee_id', $configuration->employee_id);
            $shiftTypeId = $request->input('shift_type_id', $configuration->shift_type_id);
            $startDate = $request->input('start_date', $configuration->start_date);
            $endDate = $request->has('end_date') ? $request->input('end_date') : $configuration->end_date;
            
            $existingConfig = ShiftConfiguration::where('employee_id', $employeeId)
                ->where('shift_type_id', $shiftTypeId)
                ->where('id', '!=', $id)
                ->where(function($query) use ($startDate, $endDate) {
                    // Handle date range overlap
                    if ($endDate) {
                        $query->where(function($q) use ($startDate, $endDate) {
                            $q->whereBetween('start_date', [$startDate, $endDate])
                              ->orWhereBetween('end_date', [$startDate, $endDate])
                              ->orWhere(function($inner) use ($startDate, $endDate) {
                                  $inner->where('start_date', '<=', $startDate)
                                       ->where('end_date', '>=', $endDate);
                              });
                        });
                    } else {
                        // If no end date, check if the requested start date is within any existing configuration's range
                        $query->where(function($q) use ($startDate) {
                            $q->where('start_date', '<=', $startDate)
                              ->where(function($inner) use ($startDate) {
                                  $inner->whereNull('end_date')
                                       ->orWhere('end_date', '>=', $startDate);
                              });
                        });
                    }
                })
                ->first();
                
            if ($existingConfig) {
                return $this->errorResponse('A configuration for this employee and shift type already exists in the specified date range.', 422);
            }
        }

        try {
            $configuration->update($request->all());
            
            return $this->successResponse($configuration, 'Shift configuration updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update shift configuration: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified shift configuration.
     */
    public function destroy(string $id)
    {
        $configuration = ShiftConfiguration::findOrFail($id);
        
        // Check if any shifts are using this configuration
        // This would typically be done through a relationship if such exists
        
        try {
            $configuration->delete();
            return $this->successResponse(null, 'Shift configuration deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete shift configuration: ' . $e->getMessage(), 500);
        }
    }
}