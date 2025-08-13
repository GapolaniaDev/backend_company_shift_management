<?php

namespace Tests\Feature\GraphQL;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ShiftType;
use App\Models\Shift;
use App\Models\Location;
use App\Models\PayPeriod;
use App\Models\ShiftAssignment;
use App\Models\ReplacementRequest;
use App\Models\ReplacementBid;
use App\Models\ScheduleRun;
use App\Models\ShiftTemplate;
use App\Models\TemplateException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;

class QueriesTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    private User $user;
    private Company $company;
    private Employee $employee;
    private ShiftType $shiftType;
    private Shift $shift;
    private Location $location;
    private PayPeriod $payPeriod;
    private ShiftAssignment $shiftAssignment;
    private ScheduleRun $scheduleRun;
    private ShiftTemplate $shiftTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
        ]);

        $this->user = User::create([
            'company_id' => $this->company->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '555-1234',
            'weekly_working_hours' => 40,
        ]);

        $this->shiftType = ShiftType::create([
            'company_id' => $this->company->id,
            'name' => 'Morning Shift',
            'weekly_hours' => 40,
            'description' => 'Standard morning shift',
        ]);

        $this->shift = Shift::create([
            'company_id' => $this->company->id,
            'shift_type_id' => $this->shiftType->id,
            'employee_id' => $this->employee->id,
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(8),
            'total_hours' => 8.0,
            'weekday_code' => 1,
            'shift_status' => 'published',
            'state' => 0,
        ]);

        $this->location = Location::create([
            'company_id' => $this->company->id,
            'name' => 'Main Office',
            'description' => 'Primary office location',
            'address' => '123 Main St, City, State',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'radius' => 100,
            'timezone' => 'America/New_York',
            'is_active' => true,
        ]);

        $this->payPeriod = PayPeriod::create([
            'company_id' => $this->company->id,
            'start_date' => now()->startOfWeek(),
            'end_date' => now()->endOfWeek(),
            'fiscal_week' => 1,
        ]);

        $this->shiftAssignment = ShiftAssignment::create([
            'shift_id' => $this->shift->id,
            'employee_id' => $this->employee->id,
            'status' => 'assigned',
            'assignment_type' => 'primary',
            'priority' => 1,
            'assigned_at' => now(),
        ]);

        $this->scheduleRun = ScheduleRun::create([
            'company_id' => $this->company->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'period_name' => 'Test Schedule',
            'status' => 'draft',
        ]);

        $this->shiftTemplate = ShiftTemplate::create([
            'company_id' => $this->company->id,
            'shift_type_id' => $this->shiftType->id,
            'location_id' => $this->location->id,
            'name' => 'Daily Morning Template',
            'description' => 'Standard daily morning shift template',
            'rrule' => 'FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'hours' => 8.0,
            'capacity' => 1,
            'auto_assign' => false,
            'effective_from' => now()->startOfMonth(),
            'is_active' => true,
        ]);

        // Authenticate the user for API access
        Sanctum::actingAs($this->user, ['*'], 'api');
    }

    /**
     * Test company query by ID
     */
    public function test_company_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetCompany($id: ID!) {
                company(id: $id) {
                    id
                    name
                    slug
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->company->id,
        ]);

        $response->assertJson([
            'data' => [
                'company' => [
                    'id' => (string) $this->company->id,
                    'name' => 'Test Company',
                    'slug' => 'test-company',
                ],
            ],
        ]);
    }

    /**
     * Test user query by ID
     */
    public function test_user_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetUser($id: ID!) {
                user(id: $id) {
                    id
                    name
                    email
                    role
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->user->id,
        ]);

        $response->assertJson([
            'data' => [
                'user' => [
                    'id' => (string) $this->user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'role' => 'ADMIN',
                ],
            ],
        ]);
    }

    /**
     * Test me query (authenticated user)
     */
    public function test_me_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetMe {
                me {
                    id
                    name
                    email
                    role
                    created_at
                    updated_at
                }
            }
        ');

        $response->assertJson([
            'data' => [
                'me' => [
                    'id' => (string) $this->user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'role' => 'ADMIN',
                ],
            ],
        ]);
    }

    /**
     * Test employee query by ID
     */
    public function test_employee_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetEmployee($id: ID!) {
                employee(id: $id) {
                    id
                    first_name
                    last_name
                    email
                    phone_number
                    weekly_working_hours
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->employee->id,
        ]);

        $response->assertJson([
            'data' => [
                'employee' => [
                    'id' => (string) $this->employee->id,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john.doe@example.com',
                    'phone_number' => '555-1234',
                    'weekly_working_hours' => 40,
                ],
            ],
        ]);
    }

    /**
     * Test shiftType query by ID
     */
    public function test_shift_type_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetShiftType($id: ID!) {
                shiftType(id: $id) {
                    id
                    name
                    weekly_hours
                    description
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->shiftType->id,
        ]);

        $response->assertJson([
            'data' => [
                'shiftType' => [
                    'id' => (string) $this->shiftType->id,
                    'name' => 'Morning Shift',
                    'weekly_hours' => 40,
                    'description' => 'Standard morning shift',
                ],
            ],
        ]);
    }

    /**
     * Test shift query by ID
     */
    public function test_shift_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetShift($id: ID!) {
                shift(id: $id) {
                    id
                    shift_type_id
                    employee_id
                    total_hours
                    weekday_code
                    shift_status
                    state
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->shift->id,
        ]);

        $response->assertJson([
            'data' => [
                'shift' => [
                    'id' => (string) $this->shift->id,
                    'shift_type_id' => $this->shiftType->id,
                    'employee_id' => $this->employee->id,
                    'total_hours' => 8.0,
                    'weekday_code' => 1,
                    'shift_status' => 'PUBLISHED',
                    'state' => 0,
                ],
            ],
        ]);
    }

    /**
     * Test companies paginated query
     */
    public function test_companies_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetCompanies($first: Int, $page: Int) {
                companies(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        lastPage
                        total
                        hasMorePages
                    }
                    data {
                        id
                        name
                        slug
                        created_at
                        updated_at
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'companies' => [
                    'paginatorInfo' => [
                        'currentPage',
                        'lastPage',
                        'total',
                        'hasMorePages',
                    ],
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertJson([
            'data' => [
                'companies' => [
                    'paginatorInfo' => [
                        'currentPage' => 1,
                        'total' => 1,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test activeCompanies paginated query
     */
    public function test_active_companies_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetActiveCompanies($first: Int, $page: Int) {
                activeCompanies(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        lastPage
                        total
                        hasMorePages
                    }
                    data {
                        id
                        name
                        slug
                        created_at
                        updated_at
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'activeCompanies' => [
                    'paginatorInfo' => [
                        'currentPage',
                        'lastPage',
                        'total',
                        'hasMorePages',
                    ],
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test employees paginated query
     */
    public function test_employees_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetEmployees($first: Int, $page: Int) {
                employees(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        lastPage
                        total
                        hasMorePages
                    }
                    data {
                        id
                        first_name
                        last_name
                        email
                        phone_number
                        weekly_working_hours
                        created_at
                        updated_at
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
        ]);


        $response->assertJsonStructure([
            'data' => [
                'employees' => [
                    'paginatorInfo' => [
                        'currentPage',
                        'lastPage',
                        'total',
                        'hasMorePages',
                    ],
                    'data' => [
                        '*' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'phone_number',
                            'weekly_working_hours',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertJson([
            'data' => [
                'employees' => [
                    'paginatorInfo' => [
                        'currentPage' => 1,
                        'total' => 1,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test shiftTypes paginated query
     */
    public function test_shift_types_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetShiftTypes($first: Int, $page: Int) {
                shiftTypes(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        lastPage
                        total
                        hasMorePages
                    }
                    data {
                        id
                        name
                        weekly_hours
                        description
                        created_at
                        updated_at
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'shiftTypes' => [
                    'paginatorInfo' => [
                        'currentPage',
                        'lastPage',
                        'total',
                        'hasMorePages',
                    ],
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'weekly_hours',
                            'description',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertJson([
            'data' => [
                'shiftTypes' => [
                    'paginatorInfo' => [
                        'currentPage' => 1,
                        'total' => 1,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test shifts paginated query with filters
     */
    public function test_shifts_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetShifts($first: Int, $page: Int, $filter: ShiftFilterInput) {
                shifts(first: $first, page: $page, filter: $filter) {
                    paginatorInfo {
                        currentPage
                        lastPage
                        total
                        hasMorePages
                    }
                    data {
                        id
                        shift_type_id
                        employee_id
                        total_hours
                        weekday_code
                        shift_status
                        state
                        created_at
                        updated_at
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
            'filter' => [
                'status' => 'PUBLISHED',
            ],
        ]);

        $response->assertJsonStructure([
            'data' => [
                'shifts' => [
                    'paginatorInfo' => [
                        'currentPage',
                        'lastPage',
                        'total',
                        'hasMorePages',
                    ],
                    'data' => [
                        '*' => [
                            'id',
                            'shift_type_id',
                            'employee_id',
                            'total_hours',
                            'weekday_code',
                            'shift_status',
                            'state',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertJson([
            'data' => [
                'shifts' => [
                    'paginatorInfo' => [
                        'currentPage' => 1,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test shifts query without filters
     */
    public function test_shifts_query_no_filters()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetAllShifts($first: Int, $page: Int) {
                shifts(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        lastPage
                        total
                        hasMorePages
                    }
                    data {
                        id
                        shift_type_id
                        employee_id
                        date_start
                        date_end
                        total_hours
                        shift_status
                        state
                    }
                }
            }
        ', [
            'first' => 5,
            'page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'shifts' => [
                    'paginatorInfo',
                    'data' => [
                        '*' => [
                            'id',
                            'shift_type_id',
                            'employee_id',
                            'date_start',
                            'date_end',
                            'total_hours',
                            'shift_status',
                            'state',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test error handling for non-existent resources
     */
    public function test_non_existent_resource_queries()
    {
        // Test non-existent company
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetCompany($id: ID!) {
                company(id: $id) {
                    id
                    name
                }
            }
        ', [
            'id' => 99999,
        ]);

        $response->assertJson([
            'data' => [
                'company' => null,
            ],
        ]);

        // Test non-existent user
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetUser($id: ID!) {
                user(id: $id) {
                    id
                    name
                }
            }
        ', [
            'id' => 99999,
        ]);

        $response->assertJson([
            'data' => [
                'user' => null,
            ],
        ]);

        // Test non-existent employee
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetEmployee($id: ID!) {
                employee(id: $id) {
                    id
                    first_name
                }
            }
        ', [
            'id' => 99999,
        ]);

        $response->assertJson([
            'data' => [
                'employee' => null,
            ],
        ]);
    }

    /**
     * Test authentication requirement
     */
    public function test_unauthenticated_access()
    {
        // Create a new test instance without authentication
        $this->refreshApplication();
        
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetMe {
                me {
                    id
                    name
                }
            }
        ');

        // Should return error for unauthenticated access
        $response->assertGraphQLErrorMessage('Unauthenticated.');
    }

    /**
     * Test query with relationships
     */
    public function test_query_with_relationships()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetShiftWithRelations($id: ID!) {
                shift(id: $id) {
                    id
                    total_hours
                    shiftType {
                        id
                        name
                    }
                    employee {
                        id
                        first_name
                        last_name
                    }
                }
            }
        ', [
            'id' => $this->shift->id,
        ]);

        $response->assertJson([
            'data' => [
                'shift' => [
                    'id' => (string) $this->shift->id,
                    'total_hours' => 8.0,
                    'shiftType' => [
                        'id' => (string) $this->shiftType->id,
                        'name' => 'Morning Shift',
                    ],
                    'employee' => [
                        'id' => (string) $this->employee->id,
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test location query by ID
     */
    public function test_location_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetLocation($id: ID!) {
                location(id: $id) {
                    id
                    name
                    description
                    address
                    latitude
                    longitude
                    radius
                    timezone
                    is_active
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->location->id,
        ]);

        $response->assertJson([
            'data' => [
                'location' => [
                    'id' => (string) $this->location->id,
                    'name' => 'Main Office',
                    'description' => 'Primary office location',
                    'address' => '123 Main St, City, State',
                    'latitude' => 40.7128,
                    'longitude' => -74.0060,
                    'radius' => 100,
                    'timezone' => 'America/New_York',
                    'is_active' => true,
                ],
            ],
        ]);
    }

    /**
     * Test payPeriod query by ID
     */
    public function test_pay_period_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetPayPeriod($id: ID!) {
                payPeriod(id: $id) {
                    id
                    fiscal_week
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->payPeriod->id,
        ]);

        $response->assertJson([
            'data' => [
                'payPeriod' => [
                    'id' => (string) $this->payPeriod->id,
                    'fiscal_week' => 1,
                ],
            ],
        ]);
    }

    /**
     * Test shiftAssignment query by ID
     */
    public function test_shift_assignment_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetShiftAssignment($id: ID!) {
                shiftAssignment(id: $id) {
                    id
                    shift_id
                    employee_id
                    status
                    assignment_type
                    priority
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->shiftAssignment->id,
        ]);

        $response->assertJson([
            'data' => [
                'shiftAssignment' => [
                    'id' => (string) $this->shiftAssignment->id,
                    'shift_id' => (string) $this->shift->id,
                    'employee_id' => (string) $this->employee->id,
                    'status' => 'ASSIGNED',
                    'assignment_type' => 'PRIMARY',
                    'priority' => 1,
                ],
            ],
        ]);
    }

    /**
     * Test scheduleRun query by ID
     */
    public function test_schedule_run_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetScheduleRun($id: ID!) {
                scheduleRun(id: $id) {
                    id
                    period_name
                    status
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->scheduleRun->id,
        ]);

        $response->assertJson([
            'data' => [
                'scheduleRun' => [
                    'id' => (string) $this->scheduleRun->id,
                    'period_name' => 'Test Schedule',
                    'status' => 'DRAFT',
                ],
            ],
        ]);
    }

    /**
     * Test shiftTemplate query by ID
     */
    public function test_shift_template_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetShiftTemplate($id: ID!) {
                shiftTemplate(id: $id) {
                    id
                    name
                    description
                    rrule
                    hours
                    capacity
                    auto_assign
                    is_active
                    created_at
                    updated_at
                }
            }
        ', [
            'id' => $this->shiftTemplate->id,
        ]);

        $response->assertJson([
            'data' => [
                'shiftTemplate' => [
                    'id' => (string) $this->shiftTemplate->id,
                    'name' => 'Daily Morning Template',
                    'description' => 'Standard daily morning shift template',
                    'rrule' => 'FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR',
                    'hours' => 8.0,
                    'capacity' => 1,
                    'auto_assign' => false,
                    'is_active' => true,
                ],
            ],
        ]);
    }

    /**
     * Test locations paginated query
     */
    public function test_locations_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetLocations($first: Int, $page: Int) {
                locations(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        total
                    }
                    data {
                        id
                        name
                        description
                        is_active
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'locations' => [
                    'paginatorInfo',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'is_active',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test payPeriods paginated query
     */
    public function test_pay_periods_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetPayPeriods($first: Int, $page: Int) {
                payPeriods(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        total
                    }
                    data {
                        id
                        fiscal_week
                        created_at
                        updated_at
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'payPeriods' => [
                    'paginatorInfo',
                    'data' => [
                        '*' => [
                            'id',
                            'fiscal_week',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test shiftAssignments paginated query
     */
    public function test_shift_assignments_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetShiftAssignments($first: Int, $page: Int) {
                shiftAssignments(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        total
                    }
                    data {
                        id
                        status
                        assignment_type
                        priority
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'shiftAssignments' => [
                    'paginatorInfo',
                    'data' => [
                        '*' => [
                            'id',
                            'status',
                            'assignment_type',
                            'priority',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test scheduleRuns paginated query
     */
    public function test_schedule_runs_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetScheduleRuns($first: Int, $page: Int) {
                scheduleRuns(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        total
                    }
                    data {
                        id
                        period_name
                        status
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'scheduleRuns' => [
                    'paginatorInfo',
                    'data' => [
                        '*' => [
                            'id',
                            'period_name',
                            'status',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test shiftTemplates paginated query
     */
    public function test_shift_templates_query()
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            query GetShiftTemplates($first: Int, $page: Int) {
                shiftTemplates(first: $first, page: $page) {
                    paginatorInfo {
                        currentPage
                        total
                    }
                    data {
                        id
                        name
                        description
                        is_active
                    }
                }
            }
        ', [
            'first' => 10,
            'page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'shiftTemplates' => [
                    'paginatorInfo',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'is_active',
                        ],
                    ],
                ],
            ],
        ]);
    }
}