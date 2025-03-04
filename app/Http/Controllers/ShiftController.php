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
 *     name="Turnos",
 *     description="Gestión de turnos de trabajo"
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
     *     summary="Listar turnos",
     *     description="Obtiene un listado paginado de turnos con opciones de filtrado y ordenamiento",
     *     operationId="listShifts",
     *     tags={"Turnos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filtrar por ID del empleado",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Fecha de inicio (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Fecha de fin (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="shift_type_id",
     *         in="query",
     *         description="Filtrar por tipo de turno",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Página actual",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Campo para ordenar",
     *         required=false,
     *         @OA\Schema(type="string", enum={"date_start", "date_end", "created_at", "total_hours"}, default="date_start")
     *     ),
     *     @OA\Parameter(
     *         name="sort_dir",
     *         in="query",
     *         description="Dirección de ordenamiento",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de turnos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="employee_id", type="integer", example=1),
     *                 @OA\Property(property="shift_type_id", type="integer", example=2),
     *                 @OA\Property(property="date_start", type="string", format="date-time"),
     *                 @OA\Property(property="date_end", type="string", format="date-time"),
     *                 @OA\Property(property="total_hours", type="number", format="float", example=8.5),
     *                 @OA\Property(property="location", type="string", example="Oficina Central"),
     *                 @OA\Property(property="clock_on_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="clock_off_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="employee",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="first_name", type="string", example="Juan"),
     *                     @OA\Property(property="last_name", type="string", example="Pérez")
     *                 ),
     *                 @OA\Property(
     *                     property="shift_type",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Turno Tarde")
     *                 ),
     *                 @OA\Property(
     *                     property="image_profile",
     *                     type="object",
     *                     @OA\Property(property="text_profile", type="string", example="JP"),
     *                     @OA\Property(property="text_color", type="string", example="1D5A73"),
     *                     @OA\Property(property="background_color", type="string", example="E6F1F5")
     *                 )
     *             )),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido - No tiene permisos"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de solicitud",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Supervisor account is not linked to an employee profile.")
     *         )
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
     * Get shifts for the current employee
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
     * Get shifts for the supervisor's team
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
     * Store a new shift.
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
     * Display the specified shift.
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
     * Update the specified shift.
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
     * Remove the specified shift.
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
     *     summary="Obtener turno del día",
     *     description="Obtiene el turno del día actual para el usuario autenticado",
     *     operationId="getTodayShift",
     *     tags={"Turnos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Turno encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="date_start", type="string", format="date-time"),
     *                 @OA\Property(property="date_end", type="string", format="date-time"),
     *                 @OA\Property(property="total_hours", type="number", format="float", example=8),
     *                 @OA\Property(property="location", type="string", example="Oficina Central"),
     *                 @OA\Property(property="clock_on_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="clock_off_time", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="shift_type",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Turno Mañana")
     *                 ),
     *                 @OA\Property(
     *                     property="image_profile",
     *                     type="object",
     *                     @OA\Property(property="text_profile", type="string", example="JP"),
     *                     @OA\Property(property="text_color", type="string", example="1D5A73"),
     *                     @OA\Property(property="background_color", type="string", example="E6F1F5")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No hay turno para hoy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No shift found for today")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado"
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
        
        // Add profile image data
        $shift->image_profile = $this->generateProfileTextAndColor(
            $employee->first_name, 
            $employee->last_name
        );
        
        return $this->successResponse($shift);
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
     * @OA\Put(
     *     path="/api/shifts/{id}/update-clock",
     *     summary="Registrar entrada/salida",
     *     description="Registra la hora de entrada o salida de un turno con validación de ubicación",
     *     operationId="updateClockShift",
     *     tags={"Turnos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del turno",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lat", "lng", "type"},
     *             @OA\Property(property="lat", type="number", format="float", example=19.4326),
     *             @OA\Property(property="lng", type="number", format="float", example=-99.1332),
     *             @OA\Property(property="type", type="string", enum={"clock_on", "clock_off"}, example="clock_on", description="Tipo de registro: entrada o salida")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registro exitoso",
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
     *         description="Turno no encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para modificar este turno"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="lat", type="array", @OA\Items(type="string", example="El campo lat es obligatorio")),
     *                 @OA\Property(property="type", type="array", @OA\Items(type="string", example="El tipo debe ser clock_on o clock_off"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado"
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
                ]);
            } else {
                if (!$shift->clock_on_time) {
                    return $this->errorResponse('Clock off cannot happen before clock on', 422);
                }

                $shift->update([
                    'clock_off_lat' => $request->lat,
                    'clock_off_lng' => $request->lng,
                    'clock_off_time' => now(),
                ]);
            }
            
            DB::commit();
            
            return $this->successResponse($shift, $request->type === 'clock_on' ? 'Clock in successful' : 'Clock out successful');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update clock status: ' . $e->getMessage(), 500);
        }
    }
}
