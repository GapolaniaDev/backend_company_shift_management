<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\Employee;
use App\Models\ShiftType;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $companyA;
    protected $companyB;
    protected $userA;
    protected $userB;
    protected $employeeA;
    protected $employeeB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two companies
        $this->companyA = Company::create([
            'name' => 'Company A',
            'slug' => 'company-a'
        ]);

        $this->companyB = Company::create([
            'name' => 'Company B', 
            'slug' => 'company-b'
        ]);

        // Create users for each company
        $this->userA = User::create([
            'name' => 'User A',
            'email' => 'user@companya.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'company_id' => $this->companyA->id
        ]);

        $this->userB = User::create([
            'name' => 'User B',
            'email' => 'user@companyb.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'company_id' => $this->companyB->id
        ]);

        // Create employees for each company
        $this->employeeA = Employee::create([
            'first_name' => 'Employee',
            'last_name' => 'A',
            'email' => 'employee@companya.com',
            'company_id' => $this->companyA->id
        ]);

        $this->employeeB = Employee::create([
            'first_name' => 'Employee',
            'last_name' => 'B', 
            'email' => 'employee@companyb.com',
            'company_id' => $this->companyB->id
        ]);
    }

    public function testUsersCanHaveSameEmailInDifferentCompanies()
    {
        // Should be able to create users with same email in different companies
        $userA2 = User::create([
            'name' => 'Another User A',
            'email' => 'same@email.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'company_id' => $this->companyA->id
        ]);

        $userB2 = User::create([
            'name' => 'Another User B',
            'email' => 'same@email.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'company_id' => $this->companyB->id
        ]);

        $this->assertNotEquals($userA2->id, $userB2->id);
        $this->assertEquals('same@email.com', $userA2->email);
        $this->assertEquals('same@email.com', $userB2->email);
    }

    public function testCompanyScopeIsolatesEmployees()
    {
        // Set current company to A
        app()->instance('currentCompanyId', $this->companyA->id);

        // Should only see employees from company A
        $employees = Employee::all();
        
        $this->assertCount(1, $employees);
        $this->assertEquals($this->employeeA->id, $employees->first()->id);
    }

    public function testReplacementIdValidationAcrossCompanies()
    {
        // Create shift type for each company
        $shiftTypeA = ShiftType::create([
            'name' => 'Morning Shift',
            'weekly_hours' => 40,
            'schedule' => json_encode(['Monday' => ['start' => '09:00', 'end' => '17:00']]),
            'company_id' => $this->companyA->id
        ]);

        // Create and save the shift first
        $shift = Shift::create([
            'shift_type_id' => $shiftTypeA->id,
            'employee_id' => $this->employeeA->id,
            'date_start' => now(),
            'date_end' => now()->addHours(8),
            'total_hours' => 8.0,
            'weekday_code' => now()->dayOfWeek,
            'shift_status' => 'published',
            'state' => 0,
            'company_id' => $this->companyA->id
        ]);

        // Should throw exception when trying to set replacement from different company
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Replacement employee must belong to the same company.');
        
        $shift->validateBeforeSave(['replacement_id' => $this->employeeB->id]);
    }

    public function testShiftTypesAreIsolatedByCompany()
    {
        // Create shift types for each company
        ShiftType::create([
            'name' => 'Day Shift',
            'weekly_hours' => 40,
            'schedule' => json_encode(['Monday' => ['start' => '09:00', 'end' => '17:00']]),
            'company_id' => $this->companyA->id
        ]);

        ShiftType::create([
            'name' => 'Night Shift', 
            'weekly_hours' => 40,
            'schedule' => json_encode(['Monday' => ['start' => '21:00', 'end' => '05:00']]),
            'company_id' => $this->companyB->id
        ]);

        // Set current company to A
        app()->instance('currentCompanyId', $this->companyA->id);

        $shiftTypes = ShiftType::all();
        
        $this->assertCount(1, $shiftTypes);
        $this->assertEquals('Day Shift', $shiftTypes->first()->name);
        $this->assertEquals($this->companyA->id, $shiftTypes->first()->company_id);

        // Change to company B
        app()->instance('currentCompanyId', $this->companyB->id);
        
        $shiftTypes = ShiftType::all();
        
        $this->assertCount(1, $shiftTypes);
        $this->assertEquals('Night Shift', $shiftTypes->first()->name);
        $this->assertEquals($this->companyB->id, $shiftTypes->first()->company_id);
    }

    public function testEmployeesCanHaveSameEmailInDifferentCompanies()
    {
        // Should be able to create employees with same email in different companies
        $employeeA2 = Employee::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'company_id' => $this->companyA->id
        ]);

        $employeeB2 = Employee::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com', 
            'company_id' => $this->companyB->id
        ]);

        $this->assertNotEquals($employeeA2->id, $employeeB2->id);
        $this->assertEquals('john@example.com', $employeeA2->email);
        $this->assertEquals('john@example.com', $employeeB2->email);
    }

    public function testShiftTypesCanHaveSameNameInDifferentCompanies()
    {
        // Should be able to create shift types with same name in different companies
        $shiftTypeA = ShiftType::create([
            'name' => 'Standard Shift',
            'weekly_hours' => 40,
            'schedule' => json_encode(['Monday' => ['start' => '09:00', 'end' => '17:00']]),
            'company_id' => $this->companyA->id
        ]);

        $shiftTypeB = ShiftType::create([
            'name' => 'Standard Shift',
            'weekly_hours' => 40,
            'schedule' => json_encode(['Monday' => ['start' => '09:00', 'end' => '17:00']]),
            'company_id' => $this->companyB->id
        ]);

        $this->assertNotEquals($shiftTypeA->id, $shiftTypeB->id);
        $this->assertEquals('Standard Shift', $shiftTypeA->name);
        $this->assertEquals('Standard Shift', $shiftTypeB->name);
    }
}