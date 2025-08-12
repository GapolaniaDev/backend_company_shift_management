<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ShiftConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="ShiftConfigurations",
 *     description="Endpoints for managing shift configurations, allowing role-based access for admins, supervisors, and employees."
 * )
 */
class ShiftConfigurationController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/api/shift-configurations",
     *     summary="List all shift configurations",
     *     description="Retrieves a paginated list of shift configurations. Filtering and sorting options are available.",
     *     operationId="listShiftConfigurations",
     *     tags={"ShiftConfigurations"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="shift_type_id",
     *         in="query",
     *         description="Filter by shift type ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"created_at", "updated_at"}, default="created_at")
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort_dir",
     *         in="query",
     *         description="Sort direction",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="The current page for pagination",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="The number of items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of shift configurations",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=42),
     *                 @OA\Property(property="shift_type_id", type="integer", example=3),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="meta", type="object", description="Pagination metadata")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/shift-configurations/team",
     *     summary="Get shift configurations for the supervisor's team",
     *     description="Retrieves a list of shift configurations for the employees supervised by the authenticated user.",
     *     operationId="teamShiftConfigurations",
     *     tags={"ShiftConfigurations"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee ID among supervised employees",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="shift_type_id",
     *         in="query",
     *         description="Filter by shift type ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of shift configurations for supervised employees",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=42),
     *                 @OA\Property(property="shift_type_id", type="integer", example=3),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="meta", type="object", description="Pagination metadata")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="The user is not a supervisor or is not linked to an employee profile"
     *     )
     * )
     */
    public function teamConfigurations(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
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
     * @OA\Get(
     *     path="/api/shift-configurations/my-configurations",
     *     summary="Get shift configurations for the current employee",
     *     description="Retrieves a list of shift configurations specific to the authenticated employee.",
     *     operationId="myShiftConfigurations",
     *     tags={"ShiftConfigurations"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="shift_type_id",
     *         in="query",
     *         description="Filter by shift type ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of shift configurations for the authenticated employee",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=42),
     *                 @OA\Property(property="shift_type_id", type="integer", example=3),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="meta", type="object", description="Pagination metadata")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="The user is not linked to an employee profile"
     *     )
     * )
     */
    public function myConfigurations(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
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
     * @OA\Post(
     *     path="/api/shift-configurations",
     *     operationId="storeShiftConfiguration",
     *     tags={"Shift Configuration"},
     *     summary="Store a new shift configuration",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ShiftConfiguration")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Shift configuration created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/ShiftConfiguration")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
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
            ->where(function ($query) use ($request) {
                // Handle date range overlap
                if ($request->has('end_date')) {
                    $query->where(function ($q) use ($request) {
                        $q->whereBetween('start_date', [$request->input('start_date'), $request->input('end_date')])
                            ->orWhereBetween('end_date', [$request->input('start_date'), $request->input('end_date')])
                            ->orWhere(function ($inner) use ($request) {
                                $inner->where('start_date', '<=', $request->input('start_date'))
                                    ->where('end_date', '>=', $request->input('end_date'));
                            });
                    });
                } else {
                    // If no end date, check if the requested start date is within any existing configuration's range
                    $query->where(function ($q) use ($request) {
                        $q->where('start_date', '<=', $request->input('start_date'))
                            ->where(function ($inner) use ($request) {
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
            return $this->errorResponse('Failed to create shift configuration: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/shift-configurations/{id}",
     *     summary="Retrieve a specific shift configuration by ID",
     *     description="Gets the details of a specific shift configuration identified by its ID.",
     *     operationId="getShiftConfigurationById",
     *     tags={"ShiftConfigurations"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the shift configuration",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shift configuration details",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/ShiftConfiguration")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - You do not have permission for this action"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shift configuration not found"
     *     )
     * )
     */
    public function show(string $id, Request $request)
    {
        $configuration = ShiftConfiguration::with(['employee', 'shiftType'])->findOrFail($id);

        // Role-based access control
        $user = $request->user();

        if ($user->isEmployee()) {
            // Employee can only view their own configurations
            $employee = $user->employee;
            if (! $employee || $configuration->employee_id !== $employee->id) {
                return $this->errorResponse('Unauthorized to view this configuration', 403);
            }
        } elseif ($user->isSupervisor()) {
            // Supervisor can only view configurations of their supervisees
            $employee = $user->employee;
            if ($employee) {
                $superviseeIds = Employee::where('supervisor_id', $employee->id)->pluck('id')->toArray();
                if (! in_array($configuration->employee_id, $superviseeIds)) {
                    return $this->errorResponse('Unauthorized to view this configuration', 403);
                }
            } else {
                return $this->errorResponse('Supervisor account is not linked to an employee profile.', 400);
            }
        }

        return $this->successResponse($configuration);
    }

    /**
     * @OA\Put(
     *     path="/api/shift-configurations/{id}",
     *     summary="Update a shift configuration",
     *     description="Allows an admin to update an existing shift configuration by ID.",
     *     operationId="updateShiftConfiguration",
     *     tags={"ShiftConfigurations"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the shift configuration to update",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="employee_id", type="integer", example=42, description="ID of the employee"),
     *             @OA\Property(property="shift_type_id", type="integer", example=3, description="ID of the shift type")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shift configuration successfully updated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/ShiftConfiguration")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - You do not have permission for this action"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shift configuration not found"
     *     )
     * )
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
                ->where(function ($query) use ($startDate, $endDate) {
                    // Handle date range overlap
                    if ($endDate) {
                        $query->where(function ($q) use ($startDate, $endDate) {
                            $q->whereBetween('start_date', [$startDate, $endDate])
                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                ->orWhere(function ($inner) use ($startDate, $endDate) {
                                    $inner->where('start_date', '<=', $startDate)
                                        ->where('end_date', '>=', $endDate);
                                });
                        });
                    } else {
                        // If no end date, check if the requested start date is within any existing configuration's range
                        $query->where(function ($q) use ($startDate) {
                            $q->where('start_date', '<=', $startDate)
                                ->where(function ($inner) use ($startDate) {
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
            return $this->errorResponse('Failed to update shift configuration: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/shift-configurations/{id}",
     *     summary="Delete a shift configuration",
     *     description="Deletes a shift configuration by its ID. Only accessible by admins.",
     *     operationId="deleteShiftConfiguration",
     *     tags={"ShiftConfigurations"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the shift configuration to delete",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Shift configuration successfully deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Shift configuration deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - You do not have permission for this action"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shift configuration not found"
     *     )
     * )
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
            return $this->errorResponse('Failed to delete shift configuration: '.$e->getMessage(), 500);
        }
    }
}
