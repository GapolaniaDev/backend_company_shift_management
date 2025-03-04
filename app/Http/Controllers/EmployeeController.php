<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Employees",
 *     description="Employee management endpoints"
 * )
 */
class EmployeeController extends ApiController
{
    /**
     * @OA\Get(
     *     path="/api/employees",
     *     summary="List employees",
     *     description="Returns a paginated list of employees with filtering and sorting options",
     *     operationId="listEmployees",
     *     tags={"Employees"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="supervisor_id",
     *         in="query",
     *         description="Filter by supervisor ID",
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
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"first_name", "last_name", "email", "created_at"}, default="created_at")
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
     *         description="Paginated list of employees",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="supervisor_id", type="integer", example=5, nullable=true),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Smith"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="phone_number", type="string", example="123-456-7890"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="count", type="integer", example=15),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total_pages", type="integer", example=4),
     *                 @OA\Property(
     *                     property="links",
     *                     type="object",
     *                     @OA\Property(property="next", type="string", example="http://example.com/api/employees?page=2"),
     *                     @OA\Property(property="prev", type="string", example=null),
     *                     @OA\Property(property="first", type="string", example="http://example.com/api/employees?page=1"),
     *                     @OA\Property(property="last", type="string", example="http://example.com/api/employees?page=4")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized. Insufficient permissions.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
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
        
        $query = Employee::query();
        
        // Search by name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filter by supervisor
        if ($request->has('supervisor_id')) {
            $query->where('supervisor_id', $request->input('supervisor_id'));
        }
        
        // Apply role-based access control
        $user = $request->user();
        if ($user->isSupervisor()) {
            // Supervisors can only see their supervisees
            $employee = $user->employee;
            if ($employee) {
                $query->where('supervisor_id', $employee->id);
            } else {
                return $this->errorResponse('Supervisor account is not linked to an employee profile.', 400);
            }
        }
        
        // Sort by specified column
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSortFields = ['first_name', 'last_name', 'email', 'created_at'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }
        
        $employees = $query->paginate($pageSize, ['*'], 'page', $page);
        
        return $this->paginatedResponse($employees);
    }
    
    /**
     * @OA\Get(
     *     path="/api/employees/supervisees",
     *     summary="Listar empleados supervisados",
     *     description="Obtiene un listado de empleados bajo la supervisión del usuario actual",
     *     operationId="listSupervisees",
     *     tags={"Empleados"},
     *     security={{"bearerAuth":{}}},
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de empleados supervisados",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="supervisor_id", type="integer", example=5),
     *                 @OA\Property(property="first_name", type="string", example="Juan"),
     *                 @OA\Property(property="last_name", type="string", example="Pérez"),
     *                 @OA\Property(property="email", type="string", example="juan@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de solicitud",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Your user account is not linked to an employee profile.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized. Insufficient permissions.")
     *         )
     *     )
     * )
     */
    public function supervisees(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return $this->errorResponse('Your user account is not linked to an employee profile.', 400);
        }
        
        [$page, $pageSize] = $this->getPageParams($request);
        
        $supervisees = Employee::where('supervisor_id', $employee->id)
            ->paginate($pageSize, ['*'], 'page', $page);
        
        return $this->paginatedResponse($supervisees);
    }

    /**
     * @OA\Post(
     *     path="/api/employees",
     *     summary="Crear empleado",
     *     description="Crea un nuevo empleado en el sistema",
     *     operationId="storeEmployee",
     *     tags={"Empleados"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name"},
     *             @OA\Property(property="first_name", type="string", maxLength=50, example="Juan"),
     *             @OA\Property(property="last_name", type="string", maxLength=50, example="Pérez"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=100, example="juan@example.com"),
     *             @OA\Property(property="phone_number", type="string", maxLength=20, example="123-456-7890"),
     *             @OA\Property(property="address", type="string", maxLength=255, example="123 Calle Principal"),
     *             @OA\Property(property="tax_number", type="string", maxLength=15, example="123456789"),
     *             @OA\Property(property="abn", type="string", maxLength=15, example="12345678901"),
     *             @OA\Property(property="bsb", type="string", maxLength=6, example="123456"),
     *             @OA\Property(property="account", type="string", maxLength=20, example="987654321"),
     *             @OA\Property(property="supervisor_id", type="integer", example=5),
     *             @OA\Property(property="user_id", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Empleado creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Employee created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="Juan"),
     *                 @OA\Property(property="last_name", type="string", example="Pérez"),
     *                 @OA\Property(property="email", type="string", example="juan@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
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
     *                 @OA\Property(property="first_name", type="array", @OA\Items(type="string", example="El campo first_name es obligatorio")),
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="El campo email debe ser una dirección de correo válida"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create employee: Database error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized. Insufficient permissions.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:15',
            'abn' => 'nullable|string|max:15',
            'bsb' => 'nullable|string|max:6',
            'account' => 'nullable|string|max:20',
            'supervisor_id' => 'nullable|exists:employees,id',
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            DB::beginTransaction();
            
            $employee = Employee::create($request->all());
            
            // If user_id is provided, ensure the user has the employee role
            if ($request->filled('user_id')) {
                $user = User::find($request->input('user_id'));
                if ($user && $user->role !== 'employee') {
                    $user->update(['role' => 'employee']);
                }
            }
            
            DB::commit();
            
            return $this->successResponse($employee, 'Employee created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/employees/{id}",
     *     summary="Ver empleado",
     *     description="Muestra información detallada de un empleado específico",
     *     operationId="showEmployee",
     *     tags={"Empleados"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del empleado",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del empleado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="supervisor_id", type="integer", example=5, nullable=true),
     *                 @OA\Property(property="first_name", type="string", example="Juan"),
     *                 @OA\Property(property="last_name", type="string", example="Pérez"),
     *                 @OA\Property(property="email", type="string", example="juan@example.com"),
     *                 @OA\Property(property="phone_number", type="string", example="123-456-7890"),
     *                 @OA\Property(property="address", type="string", example="123 Calle Principal"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="supervisor",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="first_name", type="string", example="Ana"),
     *                     @OA\Property(property="last_name", type="string", example="García")
     *                 ),
     *                 @OA\Property(
     *                     property="shifts",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=101),
     *                         @OA\Property(property="date_start", type="string", format="date-time"),
     *                         @OA\Property(property="date_end", type="string", format="date-time"),
     *                         @OA\Property(property="total_hours", type="number", format="float", example=8.5)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empleado no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Employee] 99")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized. Insufficient permissions.")
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        $employee = Employee::findOrFail($id);
        
        // Load related data
        $employee->load(['supervisor', 'shifts' => function($query) {
            $query->latest()->limit(10);
        }]);
        
        return $this->successResponse($employee);
    }

    /**
     * Display current employee's information.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        
        if (!$employee) {
            return $this->errorResponse('Your user account is not linked to an employee profile.', 404);
        }
        
        // Load related data
        $employee->load(['supervisor', 'shifts' => function($query) {
            $query->latest()->limit(10);
        }]);
        
        return $this->successResponse($employee);
    }

    /**
     * Update the specified employee.
     */
    public function update(Request $request, string $id)
    {
        $employee = Employee::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:50',
            'last_name' => 'sometimes|required|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:15',
            'abn' => 'nullable|string|max:15',
            'bsb' => 'nullable|string|max:6',
            'account' => 'nullable|string|max:20',
            'supervisor_id' => 'nullable|exists:employees,id',
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id,' . $employee->id,
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            DB::beginTransaction();
            
            $employee->update($request->all());
            
            // If user_id is provided, ensure the user has the employee role
            if ($request->filled('user_id') && $request->input('user_id') != $employee->user_id) {
                $user = User::find($request->input('user_id'));
                if ($user && $user->role !== 'employee') {
                    $user->update(['role' => 'employee']);
                }
            }
            
            DB::commit();
            
            return $this->successResponse($employee, 'Employee updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update employee: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign a supervisor to an employee.
     */
    public function assignSupervisor(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'supervisor_id' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        $employee = Employee::findOrFail($id);
        $supervisor = Employee::findOrFail($request->input('supervisor_id'));
        
        // Ensure the supervisor is actually a supervisor
        $supervisorUser = $supervisor->user;
        if (!$supervisorUser || !$supervisorUser->isSupervisor()) {
            return $this->errorResponse('The selected employee is not a supervisor.', 422);
        }
        
        // Prevent assigning oneself as one's own supervisor
        if ($employee->id === $supervisor->id) {
            return $this->errorResponse('An employee cannot be their own supervisor.', 422);
        }
        
        $employee->supervisor_id = $supervisor->id;
        $employee->save();
        
        return $this->successResponse($employee, 'Supervisor assigned successfully');
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(string $id)
    {
        $employee = Employee::findOrFail($id);
        
        try {
            $employee->delete();
            return $this->successResponse(null, 'Employee deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete employee: ' . $e->getMessage(), 500);
        }
    }
}