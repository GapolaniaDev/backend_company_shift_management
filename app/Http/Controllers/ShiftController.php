<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\ShiftType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Shifts",
 *     description="Work shift management endpoints"
 * )
 */
class ShiftController extends ApiController
{
    private $colorPalettes = [
        'full' => ['1D5A73', '54B5BF', '1F8C45', '97BF41', 'F2E422'],
        'bright' => ['1F8C45', '97BF41', 'F2E422', 'F2F2F2', '0D0D0D'],
        'muted' => ['1D5A73', '54B5BF', '1CA698', '1F8C45', '97BF41'],
        'deep' => ['1D5A73', '1F8C45', '97BF41', 'F2F2F2', '0D0D0D'],
        'dark' => ['1D5A73', '1CA698', '1F8C45', 'F2F2F2', '0D0D0D'],
        'gradient' => ['83ACBE', '3F7C99', 'FFFFFF'],
    ];

    private $backgroundColors = [
        'E6F1F5',
        'C2D4D9',
        'C7E4CE',
        'EAF4DC',
        'F2F2F2',
    ];

    /**
     * @OA\Get(
     *     path="/api/shifts",
     *     summary="List all shifts",
     *     description="Returns a paginated list of all shifts. Access is controlled by role: admins see all shifts, supervisors see only their team's shifts, employees can't access this endpoint.",
     *     operationId="listAllShifts",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="shift_type_id",
     *         in="query",
     *         description="Filter by shift type ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"date_start", "date_end", "created_at", "total_hours"}, default="date_start")
     *     ),
     *     @OA\Parameter(
     *         name="sort_dir",
     *         in="query",
     *         description="Sort direction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of shifts for the authenticated user",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=1),
     *                 @OA\Property(property="shift_type_id", type="integer", example=2),
     *                 @OA\Property(property="date_start", type="string", format="date-time"),
     *                 @OA\Property(property="date_end", type="string", format="date-time"),
     *                 @OA\Property(property="total_hours", type="number", format="float", example=8.5),
     *                 @OA\Property(property="location", type="string", example="Head Office"),
     *                 @OA\Property(property="clock_on_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="clock_off_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="shift_type",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Morning Shift")
     *                 )
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request)
    {
        [$page, $pageSize] = $this->getPageParams($request);

        $query = Shift::with('employee', 'shiftType');

        // Search/filter options
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->has('date_from')) {
            $query->where('date_start', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }

        if ($request->has('date_to')) {
            $query->where('date_end', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        if ($request->has('shift_type_id')) {
            $query->where('shift_type_id', $request->input('shift_type_id'));
        }

        // Role-based access control
        $user = $request->user();

        if ($user->isSupervisor()) {
            // Supervisor can only see their team's shifts
            $employee = $user->employee;
            if ($employee) {
                $superviseeIds = Employee::where('supervisor_id', $employee->id)->pluck('id')->toArray();
                $query->whereIn('employee_id', $superviseeIds);
            } else {
                return $this->errorResponse('Supervisor account is not linked to an employee profile.', 400);
            }
        }

        // Sort
        $sortBy = $request->input('sort_by', 'date_start');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSortFields = ['date_start', 'date_end', 'created_at', 'total_hours'];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $shifts = $query->paginate($pageSize, ['*'], 'page', $page);

        // Format shifts with the profile image data
        $shifts->getCollection()->transform(function ($shift) {
            $shift->image_profile = $this->generateProfileTextAndColor(
                $shift->employee->first_name,
                $shift->employee->last_name
            );
            return $shift;
        });

        return $this->paginatedResponse($shifts);
    }

    /**
     * @OA\Get(
     *     path="/api/shifts/my-shifts",
     *     summary="Get current user's shifts only",
     *     description="Returns a paginated list of shifts assigned to the authenticated user only. This endpoint is specifically for employees to view their own shifts.",
     *     operationId="getMyShifts",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Start date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="End date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="shift_type_id",
     *         in="query",
     *         description="Filter by shift type ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Current page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of user's shifts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=1),
     *                 @OA\Property(property="shift_type_id", type="integer", example=2),
     *                 @OA\Property(property="date_start", type="string", format="date-time"),
     *                 @OA\Property(property="date_end", type="string", format="date-time"),
     *                 @OA\Property(property="total_hours", type="number", format="float", example=8.5),
     *                 @OA\Property(property="location", type="string", example="Head Office"),
     *                 @OA\Property(property="clock_on_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="clock_off_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="shift_type",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Morning Shift")
     *                 ),
     *                 @OA\Property(
     *                     property="image_profile",
     *                     type="object",
     *                     @OA\Property(property="text_profile", type="string", example="JS"),
     *                     @OA\Property(property="text_color", type="string", example="1D5A73"),
     *                     @OA\Property(property="background_color", type="string", example="E6F1F5")
     *                 )
     *             )),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=10),
     *                 @OA\Property(property="count", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total_pages", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User has no employee profile",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Your user account is not linked to an employee profile.")
     *         )
     *     )
     * )
     */
    public function myShifts(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Your user account is not linked to an employee profile.', 404);
        }

        [$page, $pageSize] = $this->getPageParams($request);

        $query = Shift::with('shiftType')
            ->where('employee_id', $employee->id);

        // Date filters
        if ($request->has('date_from')) {
            $query->where('date_start', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }

        if ($request->has('date_to')) {
            $query->where('date_end', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        $shifts = $query->orderBy('date_start', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        // Add profile image data
        $shifts->getCollection()->transform(function ($shift) use ($employee) {
            $shift->image_profile = $this->generateProfileTextAndColor(
                $employee->first_name,
                $employee->last_name
            );
            return $shift;
        });

        return $this->paginatedResponse($shifts);
    }

    /**
     * @OA\Get(
     *     path="/api/shifts/team",
     *     summary="Team shifts",
     *     description="Retrieves the shifts assigned to the employees supervised by the current user (supervisor or administrator).",
     *     operationId="getTeamShifts",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of team shifts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=5),
     *                 @OA\Property(property="shift_type_id", type="integer", example=3),
     *                 @OA\Property(property="date_start", type="string", format="date-time", example="2023-10-13T09:00:00Z"),
     *                 @OA\Property(property="date_end", type="string", format="date-time", example="2023-10-13T17:00:00Z"),
     *                 @OA\Property(property="location", type="string", example="Branch A")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function teamShifts(Request $request)
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

        $query = Shift::with(['employee', 'shiftType'])
            ->whereIn('employee_id', $superviseeIds);

        // Date filters
        if ($request->has('date_from')) {
            $query->where('date_start', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }

        if ($request->has('date_to')) {
            $query->where('date_end', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        // Employee filter
        if ($request->has('employee_id') && in_array($request->input('employee_id'), $superviseeIds)) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        $shifts = $query->orderBy('date_start', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        // Add profile image data
        $shifts->getCollection()->transform(function ($shift) {
            $shift->image_profile = $this->generateProfileTextAndColor(
                $shift->employee->first_name,
                $shift->employee->last_name
            );
            return $shift;
        });

        return $this->paginatedResponse($shifts);
    }

    /**
     * @OA\Post(
     *     path="/api/shifts",
     *     summary="Create a shift",
     *     description="Creates a new shift in the system.",
     *     operationId="createShift",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="employee_id", type="integer", example=5),
     *             @OA\Property(property="shift_type_id", type="integer", example=1),
     *             @OA\Property(property="date_start", type="string", format="date-time", example="2023-10-12T09:00:00Z"),
     *             @OA\Property(property="date_end", type="string", format="date-time", example="2023-10-12T17:00:00Z"),
     *             @OA\Property(property="location", type="string", example="Central Office")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Shift successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=5),
     *                 @OA\Property(property="shift_type_id", type="integer", example=1),
     *                 @OA\Property(property="date_start", type="string", format="date-time", example="2023-10-12T09:00:00Z"),
     *                 @OA\Property(property="date_end", type="string", format="date-time", example="2023-10-12T17:00:00Z"),
     *                 @OA\Property(property="location", type="string", example="Central Office")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'shift_type_id' => 'required|exists:shift_types,id',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after:date_start',
            'total_hours' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $shift = Shift::create($request->all());

            return $this->successResponse($shift, 'Shift created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create shift: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/shifts/{id}",
     *     summary="Show a shift",
     *     description="Retrieves the details of a specific shift by its ID.",
     *     operationId="getShift",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Shift ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=5),
     *                 @OA\Property(property="shift_type_id", type="integer", example=3),
     *                 @OA\Property(property="date_start", type="string", format="date-time", example="2023-10-12T09:00:00Z"),
     *                 @OA\Property(property="date_end", type="string", format="date-time", example="2023-10-12T17:00:00Z"),
     *                 @OA\Property(property="location", type="string", example="Central Office")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shift not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Shift not found")
     *         )
     *     )
     * )
     */
    public function show(string $id, Request $request)
    {
        $shift = Shift::with(['employee', 'shiftType'])->findOrFail($id);

        // Role-based access control
        $user = $request->user();

        if ($user->isEmployee()) {
            // Employee can only view their own shifts
            $employee = $user->employee;
            if (!$employee || $shift->employee_id !== $employee->id) {
                return $this->errorResponse('Unauthorized to view this shift', 403);
            }
        } else if ($user->isSupervisor()) {
            // Supervisor can only view shifts of their supervisees
            $employee = $user->employee;
            if ($employee) {
                $superviseeIds = Employee::where('supervisor_id', $employee->id)->pluck('id')->toArray();
                if (!in_array($shift->employee_id, $superviseeIds)) {
                    return $this->errorResponse('Unauthorized to view this shift', 403);
                }
            } else {
                return $this->errorResponse('Supervisor account is not linked to an employee profile.', 400);
            }
        }

        // Add profile image data
        $shift->image_profile = $this->generateProfileTextAndColor(
            $shift->employee->first_name,
            $shift->employee->last_name
        );

        return $this->successResponse($shift);
    }

    /**
     * @OA\Put(
     *     path="/api/shifts/{id}",
     *     summary="Update a shift",
     *     description="Updates the details of an existing shift.",
     *     operationId="updateShift",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Shift ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="employee_id", type="integer", example=5),
     *             @OA\Property(property="shift_type_id", type="integer", example=1),
     *             @OA\Property(property="date_start", type="string", format="date-time", example="2023-10-12T09:00:00Z"),
     *             @OA\Property(property="date_end", type="string", format="date-time", example="2023-10-12T17:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $shift = Shift::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|required|exists:employees,id',
            'shift_type_id' => 'sometimes|required|exists:shift_types,id',
            'date_start' => 'sometimes|required|date',
            'date_end' => 'sometimes|required|date|after:date_start',
            'total_hours' => 'sometimes|required|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $shift->update($request->all());

            return $this->successResponse($shift, 'Shift updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update shift: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/shifts/{id}",
     *     summary="Delete a shift",
     *     description="Deletes a shift from the system by its ID.",
     *     operationId="deleteShift",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Shift ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift successfully deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Shift successfully deleted")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $shift = Shift::findOrFail($id);

        try {
            $shift->delete();
            return $this->successResponse(null, 'Shift deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete shift: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/shifts/today",
     *     summary="Get today's shift",
     *     description="Retrieves the current day's shift for the authenticated user",
     *     operationId="getTodayShift",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Shift found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="date_start", type="string", format="date-time"),
     *                 @OA\Property(property="date_end", type="string", format="date-time"),
     *                 @OA\Property(property="total_hours", type="number", format="float", example=8),
     *                 @OA\Property(property="location", type="string", example="Head Office"),
     *                 @OA\Property(property="clock_on_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="clock_off_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="shift_type",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Morning Shift")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No shift for today",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No shift found for today")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function getTodayShift(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Your user account is not linked to an employee profile.', 404);
        }

        $today = Carbon::now()->toDateString();

        $shift = Shift::with('shiftType')
            ->where('employee_id', $employee->id)
            ->whereDate('date_start', '<=', $today)
            ->whereDate('date_end', '>=', $today)
            ->first();

        if (!$shift) {
            return $this->errorResponse('No shift found for today', 404);
        }

        return $this->successResponse($shift);
    }


    /**
     * @OA\Put(
     *     path="/api/shifts/{id}/update-clock",
     *     summary="Register clock in/out",
     *     description="Registers the clock-in or clock-out time for a shift with location validation",
     *     operationId="updateClockShift",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Shift ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lat", "lng", "type"},
     *             @OA\Property(property="lat", type="number", format="float", example=19.4326),
     *             @OA\Property(property="lng", type="number", format="float", example=-99.1332),
     *             @OA\Property(property="type", type="string", enum={"clock_on", "clock_off"}, example="clock_on", description="Type of record: clock in or clock out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful registration",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Clock in successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="clock_on_time", type="string", format="date-time"),
     *                 @OA\Property(property="clock_off_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="clock_on_lat", type="number", format="float", example=19.4326),
     *                 @OA\Property(property="clock_on_lng", type="number", format="float", example=-99.1332),
     *                 @OA\Property(property="clock_off_lat", type="number", format="float", nullable=true),
     *                 @OA\Property(property="clock_off_lng", type="number", format="float", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shift not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Not authorized to modify this shift"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="lat", type="array", @OA\Items(type="string", example="The lat field is required")),
     *                 @OA\Property(property="type", type="array", @OA\Items(type="string", example="The type must be clock_on or clock_off"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function updateClock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'type' => 'required|in:clock_on,clock_off',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        $shift = Shift::findOrFail($id);
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Your user account is not linked to an employee profile.', 404);
        }

        // Check if the shift belongs to the employee
        if ($shift->employee_id !== $employee->id) {
            return $this->errorResponse('Unauthorized to update this shift', 403);
        }

        // Check if within the allowed radius
        if (!$shift->isWithinRadius($request->lat, $request->lng)) {
            return $this->errorResponse('You are outside the allowed radius for this location', 422);
        }

        try {
            DB::beginTransaction();

            if ($request->type === 'clock_on') {
                if ($shift->clock_off_time) {
                    return $this->errorResponse('Clock on cannot be updated after clock off', 422);
                }

                $shift->update([
                    'clock_on_lat' => $request->lat,
                    'clock_on_lng' => $request->lng,
                    'clock_on_time' => now(),
                    'state' => Shift::STATE_STARTED,
                ]);
            } else {
                if (!$shift->clock_on_time) {
                    return $this->errorResponse('Clock off cannot happen before clock on', 422);
                }

                $shift->update([
                    'clock_off_lat' => $request->lat,
                    'clock_off_lng' => $request->lng,
                    'clock_off_time' => now(),
                    'state' => Shift::STATE_FINISHED,
                ]);
            }

            DB::commit();

            return $this->successResponse($shift, $request->type === 'clock_on' ? 'Clock in successful' : 'Clock out successful');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update clock status: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Generate profile text and color.
     */
    public function generateProfileTextAndColor($var1, $var2)
    {
        $firstLetter1 = strtoupper(substr($var1, 0, 1));
        $firstLetter2 = strtoupper(substr($var2, 0, 1));
        $textProfile = $firstLetter1 . $firstLetter2;
        $textColor = $this->generateColorForTextProfile($textProfile);
        $backgroundColor = $this->generateBackgroundColorForTextProfile($textProfile);
        return [
            'text_profile' => $textProfile,
            'text_color' => $textColor,
            'background_color' => $backgroundColor,
        ];
    }

    private function generateColorForTextProfile($textProfile)
    {
        $palettes = array_merge(...array_values($this->colorPalettes));
        $hashValue = crc32($textProfile);
        $index = $hashValue % count($palettes);
        return $palettes[$index];
    }

    private function generateBackgroundColorForTextProfile($textProfile)
    {
        $hashValue = crc32($textProfile);
        $index = $hashValue % count($this->backgroundColors);
        return $this->backgroundColors[$index];
    }
}
