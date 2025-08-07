<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\ShiftType;
use App\Services\ShiftService;
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
    protected $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

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
     *     description="Creates a new shift in the system with validations for overlapping shifts and weekly hour limits.",
     *     operationId="createShift",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="employee_id", type="integer", example=5, description="Employee ID (verified for availability)"),
     *             @OA\Property(property="shift_type_id", type="integer", example=1, description="Shift type ID"),
     *             @OA\Property(property="date_start", type="string", format="date-time", example="2023-10-12T09:00:00Z", description="Start date/time (verified against overlapping shifts)"),
     *             @OA\Property(property="date_end", type="string", format="date-time", example="2023-10-12T17:00:00Z", description="End date/time (must be after start time)"),
     *             @OA\Property(property="total_hours", type="number", format="float", example=8.0, description="Total shift hours (used for weekly limit validation)"),
     *             @OA\Property(property="location", type="string", example="Central Office", description="Work location"),
     *             @OA\Property(property="comments", type="string", example="Covering for John", description="Additional notes about the shift"),
     *             @OA\Property(property="latitude", type="number", format="float", example=19.4326, description="Location latitude (used for timezone calculation)"),
     *             @OA\Property(property="longitude", type="number", format="float", example=-99.1332, description="Location longitude (used for timezone calculation)")
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
     *                 @OA\Property(property="total_hours", type="number", format="float", example=8.0),
     *                 @OA\Property(property="location", type="string", example="Central Office"),
     *                 @OA\Property(property="comments", type="string", example="Covering for John"),
     *                 @OA\Property(property="weekday_code", type="integer", example=4)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The shift overlaps with other shifts assigned to the employee"),
     *             @OA\Property(property="errors", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You are not authorized to create shifts")
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
            'comments' => 'nullable|string|max:1000',
            'latitude' => 'required_with:longitude|numeric',
            'longitude' => 'required_with:latitude|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $data = $request->all();

            // Ensure dates are in UTC
            if (isset($data['date_start'])) {
                $data['date_start'] = Carbon::parse($data['date_start'])->setTimezone('UTC');
            }

            if (isset($data['date_end'])) {
                $data['date_end'] = Carbon::parse($data['date_end'])->setTimezone('UTC');
            }

            // Calculate timezone based on coordinates
            if ($request->has('latitude') && $request->has('longitude')) {
                $timezone = $this->getTimezoneFromCoordinates($request->latitude, $request->longitude);
                $data['date_start_timezone'] = $timezone;
                $data['date_end_timezone'] = $timezone;
            }

            // Validate creation through the service (includes overlap and hour limit validations)
            [$success, $result] = $this->shiftService->createShift($data);

            if (!$success) {
                return $this->errorResponse($result, 422);
            }

            return $this->successResponse($result, 'Shift created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create shift: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get timezone from coordinates using TimeZoneDB API
     *
     * @param float $latitude
     * @param float $longitude
     * @return string Timezone name (e.g. 'America/New_York') or 'UTC' if not found
     */
    private function getTimezoneFromCoordinates($latitude, $longitude)
    {
        try {
            // First we try to get the timezone with this method that doesn't require an external API
            $timezone = $this->getTimezoneFromCoordinatesLocal($latitude, $longitude);
            if ($timezone) {
                return $timezone;
            }

            // If the local method fails, we try with the TimeZoneDB API
            $apiKey = env('TIMEZONEDB_API_KEY', ''); // TimeZoneDB API key

            if (empty($apiKey)) {
                // If there's no API key, we try with another free API
                $url = "https://api.ipgeolocation.io/timezone?lat={$latitude}&long={$longitude}";
                $response = file_get_contents($url);
                $data = json_decode($response, true);

                if (isset($data['timezone']) && !empty($data['timezone'])) {
                    return $data['timezone'];
                }

                // If everything fails, we return UTC
                return 'UTC';
            }

            $url = "http://api.timezonedb.com/v2.1/get-time-zone?key={$apiKey}&format=json&by=position&lat={$latitude}&lng={$longitude}";
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if ($data && isset($data['status']) && $data['status'] === 'OK' && isset($data['zoneName'])) {
                return $data['zoneName'];
            }

            // If it fails, we return UTC
            return 'UTC';
        } catch (\Exception $e) {
            // In case of error, we return UTC
            return 'UTC';
        }
    }

    /**
     * Get timezone from coordinates using PHP's DateTimeZone class
     * This method does not require external APIs but is less accurate
     *
     * @param float $latitude
     * @param float $longitude
     * @return string|null Timezone name or null if not found
     */
    private function getTimezoneFromCoordinatesLocal($latitude, $longitude)
    {
        try {
            // Get all timezone identifiers
            $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

            // Set a very large distance initially
            $minDistance = PHP_INT_MAX;
            $closestTimezone = null;

            // Loop through each timezone
            foreach ($timezones as $timezone) {
                $tz = new \DateTimeZone($timezone);
                $location = $tz->getLocation();

                if (!$location) {
                    continue;
                }

                $tzLatitude = $location['latitude'];
                $tzLongitude = $location['longitude'];

                // Calculate the distance between input coordinates and timezone coordinates
                $distance = $this->calculateDistance($latitude, $longitude, $tzLatitude, $tzLongitude);

                // Update closest timezone if this one is closer
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $closestTimezone = $timezone;
                }
            }

            return $closestTimezone;
        } catch (\Exception $e) {
            return null;
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

        // Add local times in their respective timezones
        if ($shift->clock_on_time) {
            $shift->local_clock_on_time = $shift->getLocalClockOnTime()->toDateTimeString();
        }

        if ($shift->clock_off_time) {
            $shift->local_clock_off_time = $shift->getLocalClockOffTime()->toDateTimeString();
        }

        // Add shift start/end times in their local timezones
        if ($shift->date_start) {
            $shift->local_date_start = $shift->getLocalStartTime()->toDateTimeString();
        }

        if ($shift->date_end) {
            $shift->local_date_end = $shift->getLocalEndTime()->toDateTimeString();
        }

        return $this->successResponse($shift);
    }

    /**
     * @OA\Put(
     *     path="/api/shifts/{id}",
     *     summary="Update a shift",
     *     description="Updates the details of an existing shift with validations for overlapping shifts and weekly hour limits.",
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
     *             @OA\Property(property="employee_id", type="integer", example=5, description="Employee ID (verified for availability)"),
     *             @OA\Property(property="shift_type_id", type="integer", example=1, description="Shift type ID"),
     *             @OA\Property(property="date_start", type="string", format="date-time", example="2023-10-12T09:00:00Z", description="Start date/time (verified against overlapping shifts)"),
     *             @OA\Property(property="date_end", type="string", format="date-time", example="2023-10-12T17:00:00Z", description="End date/time (must be after start time)"),
     *             @OA\Property(property="total_hours", type="number", format="float", example=8.0, description="Total shift hours (used for weekly limit validation)"),
     *             @OA\Property(property="location", type="string", example="Central Office", description="Work location"),
     *             @OA\Property(property="comments", type="string", example="Covering for John", description="Additional notes about the shift")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=5),
     *                 @OA\Property(property="shift_type_id", type="integer", example=1),
     *                 @OA\Property(property="date_start", type="string", format="date-time", example="2023-10-12T09:00:00Z"),
     *                 @OA\Property(property="date_end", type="string", format="date-time", example="2023-10-12T17:00:00Z"),
     *                 @OA\Property(property="total_hours", type="number", format="float", example=8.0),
     *                 @OA\Property(property="location", type="string", example="Central Office")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The shift exceeds the employee's weekly limit of 40 hours."),
     *             @OA\Property(property="errors", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shift not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Shift not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You are not authorized to update this shift")
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
            'comments' => 'nullable|string|max:1000',
            'latitude' => 'sometimes|required_with:longitude|numeric',
            'longitude' => 'sometimes|required_with:latitude|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $data = $request->all();

            // Ensure dates are in UTC
            if (isset($data['date_start'])) {
                $data['date_start'] = Carbon::parse($data['date_start'])->setTimezone('UTC');
            }

            if (isset($data['date_end'])) {
                $data['date_end'] = Carbon::parse($data['date_end'])->setTimezone('UTC');
            }

            // Calculate timezone based on coordinates if new ones were provided
            if ($request->has('latitude') && $request->has('longitude')) {
                $timezone = $this->getTimezoneFromCoordinates($request->latitude, $request->longitude);
                $data['date_start_timezone'] = $timezone;
                $data['date_end_timezone'] = $timezone;
            }

            // Validate update through the service (includes overlap and hour limit validations)
            [$success, $result] = $this->shiftService->updateShift($id, $data);

            if (!$success) {
                return $this->errorResponse($result, 422);
            }

            return $this->successResponse($result, 'Shift updated successfully');
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
     *             required={"lat", "lng", "type", "timezone"},
     *             @OA\Property(property="lat", type="number", format="float", example=19.4326),
     *             @OA\Property(property="lng", type="number", format="float", example=-99.1332),
     *             @OA\Property(property="type", type="string", enum={"clock_on", "clock_off"}, example="clock_on", description="Type of record: clock in or clock out"),
     *             @OA\Property(property="timezone", type="string", example="America/New_York", description="The timezone where the employee is located at clock time")
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
     *                 @OA\Property(property="timezone_start", type="string", example="America/New_York"),
     *                 @OA\Property(property="timezone_end", type="string", example="America/Los_Angeles", nullable=true),
     *                 @OA\Property(property="local_clock_on_time", type="string", format="date-time"),
     *                 @OA\Property(property="local_clock_off_time", type="string", format="date-time", nullable=true),
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
            'timezone' => 'required|string|max:64',
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

                // Store the time in UTC but include the timezone information
                $shift->update([
                    'clock_on_lat' => $request->lat,
                    'clock_on_lng' => $request->lng,
                    'clock_on_time' => now()->setTimezone('UTC'),
                    'timezone_start' => $request->timezone,
                    'state' => Shift::STATE_STARTED,
                ]);
            } else {
                if (!$shift->clock_on_time) {
                    return $this->errorResponse('Clock off cannot happen before clock on', 422);
                }

                // Store the time in UTC but include the timezone information
                $shift->update([
                    'clock_off_lat' => $request->lat,
                    'clock_off_lng' => $request->lng,
                    'clock_off_time' => now()->setTimezone('UTC'),
                    'timezone_end' => $request->timezone,
                    'state' => Shift::STATE_FINISHED,
                ]);
            }

            DB::commit();

            // Add the local time in the response to show the correct time in the user's timezone
            if ($request->type === 'clock_on' && $shift->clock_on_time) {
                $shift->local_clock_on_time = $shift->getLocalClockOnTime()->toDateTimeString();
            } else if ($request->type === 'clock_off' && $shift->clock_off_time) {
                $shift->local_clock_off_time = $shift->getLocalClockOffTime()->toDateTimeString();
            }

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

    /**
     * Transform a shift object to include only required fields
     *
     * @param \App\Models\Shift $shift
     * @return array
     */
    private function transformShift($shift)
    {
        if (!$shift) {
            return null;
        }

        // Add local times if they don't exist yet
        if ($shift->date_start && !isset($shift->local_date_start)) {
            $shift->local_date_start = $shift->getLocalStartTime()->toDateTimeString();
        }

        if ($shift->date_end && !isset($shift->local_date_end)) {
            $shift->local_date_end = $shift->getLocalEndTime()->toDateTimeString();
        }

        $start = Carbon::parse($shift->clock_on_time);
        $end = Carbon::parse($shift->clock_off_time);
        $total_minutes = $start->diffInMinutes($end);
        // Return only the required fields
        return [
            'id' => $shift->id,
            'date_start' => $shift->date_start,
            'date_start_timezone' => $shift->date_start_timezone,
            'date_end' => $shift->date_end,
            'date_end_timezone' => $shift->date_end_timezone,

            'clock_on_time' => $shift->clock_on_time,
            'local_clock_on_time' => $shift->timezone_start,
            'clock_off_time' => $shift->clock_off_time,
            'local_clock_off_time' => $shift->timezone_end,

            'total_minutes' => $total_minutes,

            'total_hours' => $shift->total_hours,
            'weekday_code' => $shift->weekday_code,
            'comments' => $shift->comments,
            'local_date_start' => $shift->local_date_start ?? null,
            'local_date_end' => $shift->local_date_end ?? null,
            'state' => $shift->state,
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/shifts/by-range",
     *     summary="Get shifts by date range",
     *     description="Allows getting shifts filtered by date range, employees and locations, grouped by day, week or month",
     *     operationId="getShiftsByRange",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date in YYYY-MM-DD format",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date in YYYY-MM-DD format",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="view_type",
     *         in="query",
     *         description="View type to group results: daily, weekly, monthly",
     *         required=false,
     *         @OA\Schema(type="string", enum={"daily", "weekly", "monthly"}, default="daily")
     *     ),
     *     @OA\Parameter(
     *         name="user_ids[]",
     *         in="query",
     *         description="Employee IDs to filter (can be repeated for multiple values)",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="integer"))
     *     ),
     *     @OA\Parameter(
     *         name="location_ids[]",
     *         in="query",
     *         description="Location IDs to filter (can be repeated for multiple values)",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="integer"))
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully obtained shifts grouped by view type",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\AdditionalProperties(
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="date_start", type="string", format="date-time", example="2023-11-07T09:00:00Z"),
     *                         @OA\Property(property="date_end", type="string", format="date-time", example="2023-11-07T17:00:00Z"),
     *                         @OA\Property(property="employee_id", type="integer", example=5),
     *                         @OA\Property(property="location", type="string", example="Downtown Office"),
     *                         @OA\Property(property="shift_type_id", type="integer", example=2),
     *                         @OA\Property(property="local_date_start", type="string", format="date-time", example="2023-11-07T09:00:00"),
     *                         @OA\Property(property="local_date_end", type="string", format="date-time", example="2023-11-07T17:00:00"),
     *                         @OA\Property(
     *                             property="employee",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=5),
     *                             @OA\Property(property="name", type="string", example="John Doe"),
     *                             @OA\Property(property="first_name", type="string", example="John"),
     *                             @OA\Property(property="last_name", type="string", example="Doe")
     *                         ),
     *                         @OA\Property(
     *                             property="shift_type",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="name", type="string", example="Day Shift"),
     *                             @OA\Property(property="color", type="string", example="#1D5A73")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error de validación"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado"
     *     )
     * )
     */
    public function getShiftsByRange(Request $request)
    {
        // Validate input parameters
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'view_type' => 'nullable|in:daily,weekly,monthly',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:employees,id',
            'location_ids' => 'nullable|array',
            'location_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        // Get request parameters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $viewType = $request->input('view_type', 'daily'); // Default 'daily'
        $userIds = $request->input('user_ids', []);
        $locationIds = $request->input('location_ids', []);

        try {
            // Perform role-based permission verification
            $user = $request->user();

            // If it's an employee, they can only see their own shifts
            if ($user->isEmployee()) {
                $employee = $user->employee;
                if (!$employee) {
                    return $this->errorResponse('Your account is not linked to an employee profile.', 404);
                }
                // Override userIds so they only see their own shifts
                $userIds = [$employee->id];
            } // If it's a supervisor, they can only see their team's shifts
            else if ($user->isSupervisor()) {
                $employee = $user->employee;
                if (!$employee) {
                    return $this->errorResponse('Your supervisor account is not linked to an employee profile.', 400);
                }

                // If userIds were specified, verify that they are from the supervisor's team
                if (!empty($userIds)) {
                    $superviseeIds = Employee::where('supervisor_id', $employee->id)->pluck('id')->toArray();

                    // Filter to only include those who are on the team
                    $validUserIds = array_intersect($userIds, $superviseeIds);

                    // If there are specified userIds but none are valid, return error
                    if (empty($validUserIds) && !empty($userIds)) {
                        return $this->errorResponse('You do not have permission to view these employees\' shifts.', 403);
                    }

                    $userIds = $validUserIds;
                } else {
                    // If none were specified, get all from the team
                    $superviseeIds = Employee::where('supervisor_id', $employee->id)->pluck('id')->toArray();
                    $userIds = $superviseeIds;
                }
            }
            // Administrators can see all shifts (no additional restriction)

            // Get shifts from the service
            $shifts = $this->shiftService->getShiftsByRange($startDate, $endDate, $viewType, $userIds, $locationIds);

            return $this->successResponse($shifts);
        } catch (\Exception $e) {
            return $this->errorResponse('Error getting shifts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/shifts/shift-history",
     *     summary="Get shift history and date summary",
     *     description="Retrieves the current shift for a specific date, history of the 10 most recent shifts, and date summary with shift counts for a 7-day range (3 days before and 3 days after the specified date)",
     *     operationId="getShiftHistory",
     *     tags={"Shifts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Date to retrieve shift for (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift history data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="current_shift",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="date_start", type="string", format="date-time", example="2025-03-10T09:00:00Z"),
     *                     @OA\Property(property="date_start_timezone", type="string", example="Australia/Adelaide"),
     *                     @OA\Property(property="date_end", type="string", format="date-time", example="2025-03-10T17:00:00Z"),
     *                     @OA\Property(property="date_end_timezone", type="string", example="Australia/Adelaide"),
     *                     @OA\Property(property="total_hours", type="number", format="float", example=8),
     *                     @OA\Property(property="weekday_code", type="integer", example=1),
     *                     @OA\Property(property="comments", type="string", nullable=true, example="Turno realizado sin problemas"),
     *                     @OA\Property(property="local_date_start", type="string", format="date-time", example="2025-03-10T09:00:00Z"),
     *                     @OA\Property(property="local_date_end", type="string", format="date-time", example="2025-03-10T17:00:00Z"),
     *                     @OA\Property(property="state", type="integer", example=0)
     *                 ),
     *                 @OA\Property(
     *                     property="shift_history",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="date_start", type="string", format="date-time", example="2025-03-09T09:00:00Z"),
     *                         @OA\Property(property="date_start_timezone", type="string", example="Australia/Adelaide"),
     *                         @OA\Property(property="date_end", type="string", format="date-time", example="2025-03-09T17:00:00Z"),
     *                         @OA\Property(property="date_end_timezone", type="string", example="Australia/Adelaide"),
     *                         @OA\Property(property="total_hours", type="number", format="float", example=8),
     *                         @OA\Property(property="weekday_code", type="integer", example=1),
     *                         @OA\Property(property="comments", type="string", nullable=true, example="Turno muy productivo"),
     *                         @OA\Property(property="local_date_start", type="string", format="date-time", example="2025-03-09T09:00:00Z"),
     *                         @OA\Property(property="local_date_end", type="string", format="date-time", example="2025-03-09T17:00:00Z"),
     *                         @OA\Property(property="state", type="integer", example=2)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="date_summary",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="date", type="string", format="date", example="2025-03-07"),
     *                         @OA\Property(property="total_shifts", type="integer", example=0)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User has no employee profile",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Your user account is not linked to an employee profile.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function shiftHistory(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Your user account is not linked to an employee profile.', 404);
        }

        // Get date parameter or use current date
        $date = $request->has('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : Carbon::now()->startOfDay();

        // Calculate date range (3 days before and 3 days after)
        $startRange = (clone $date)->subDays(3);
        $endRange = (clone $date)->addDays(3);

        // Current shift for the specified date
        $currentShift = Shift::with(['shiftType'])
            ->where('employee_id', $employee->id)
            ->whereDate('date_start', '<=', $date)
            ->whereDate('date_end', '>=', $date)
            ->first();

        // Get the 10 most recent shifts from any date until the current date
        $shiftHistory = Shift::with(['shiftType'])
            ->where('employee_id', $employee->id)
            ->whereDate('date_end', '<=', now()) // Solo turnos hasta la fecha actual
            ->orderBy('date_end', 'desc')
            ->limit(10)
            ->get();

        // Generate date summary array
        $dateSummary = [];
        $currentDate = clone $startRange;

        // Count shifts for each day in the date range
        while ($currentDate <= $endRange) {
            $dateStr = $currentDate->format('Y-m-d');

            // Count shifts for this day
            $shiftsCount = Shift::where('employee_id', $employee->id)
                ->whereDate('date_start', '<=', $dateStr)
                ->whereDate('date_end', '>=', $dateStr)
                ->count();

            $dateSummary[] = [
                'date' => $dateStr,
                'total_shifts' => $shiftsCount
            ];

            $currentDate->addDay();
        }

        // Process data for the response
        if ($currentShift) {
            // Add local times
            if ($currentShift->date_start) {
                $currentShift->local_date_start = $currentShift->getLocalStartTime()->toDateTimeString();
            }

            if ($currentShift->date_end) {
                $currentShift->local_date_end = $currentShift->getLocalEndTime()->toDateTimeString();
            }
        }

        // Add local times to history shifts
        foreach ($shiftHistory as $shift) {
            if ($shift->date_start) {
                $shift->local_date_start = $shift->getLocalStartTime()->toDateTimeString();
            }

            if ($shift->date_end) {
                $shift->local_date_end = $shift->getLocalEndTime()->toDateTimeString();
            }
        }

        // Transform shifts to include only required fields
        $transformedCurrentShift = $this->transformShift($currentShift);
        $transformedShiftHistory = $shiftHistory->map(function ($shift) {
            return $this->transformShift($shift);
        });

        return $this->successResponse([
            'current_shift' => $transformedCurrentShift,
            'shift_history' => $transformedShiftHistory,
            'date_summary' => $dateSummary
        ]);
    }
}
