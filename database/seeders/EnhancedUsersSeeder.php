<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use App\Models\Employee;
use Faker\Factory as Faker;

class EnhancedUsersSeeder extends Seeder
{
    private $faker;
    private $employeeTypes = ['full-time', 'part-time', 'casual', 'contractor'];
    private $positions = ['Cleaner', 'Senior Cleaner', 'Team Leader', 'Maintenance Staff', 'Specialist Cleaner'];
    
    public function __construct()
    {
        $this->faker = Faker::create('en_AU');
    }

    public function run(): void
    {
        $companies = Company::whereIn('slug', ['dimeo', 'corporate-clean', 'bio-green-family', 'adelaide-facility-services'])->get();
        
        $this->command->info('👥 Creating enhanced user structure for ' . $companies->count() . ' companies...');
        
        foreach ($companies as $company) {
            $this->command->info("🏢 Creating users for {$company->name}...");
            
            // Create 1 Admin per company
            $this->createCompanyAdmin($company);
            
            // Get locations for this company
            $locations = Location::where('company_id', $company->id)->get();
            $this->command->info("📍 Found {$locations->count()} locations for {$company->name}");
            
            // Create 1 Supervisor per location + their supervised employees
            foreach ($locations as $location) {
                $this->createLocationSupervisorAndEmployees($company, $location);
            }
            
            $this->command->info("✅ Completed user creation for {$company->name}");
        }
        
        $this->command->info('🎉 Enhanced user structure completed successfully!');
        $this->printSummary();
    }
    
    private function createCompanyAdmin(Company $company): void
    {
        $adminUser = User::create([
            'name' => "Admin {$company->name}",
            'email' => strtolower(str_replace([' ', '-'], '_', $company->slug)) . '_admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('123456789'),
            'role' => 'admin',
            'company_id' => $company->id,
        ]);
        
        // Create employee profile for admin
        Employee::create([
            'user_id' => $adminUser->id,
            'company_id' => $company->id,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $adminUser->email,
            'phone_number' => $this->faker->phoneNumber(),
            'weekly_working_hours' => 38,
        ]);
        
        $this->command->info("   👤 Created Admin: {$adminUser->email}");
    }
    
    private function createLocationSupervisorAndEmployees(Company $company, Location $location): void
    {
        // Create Supervisor
        $supervisorUser = User::create([
            'name' => "Supervisor {$location->name}",
            'email' => strtolower(str_replace([' ', '-', '\'', '.', ','], '_', $location->name)) . '_supervisor@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('123456789'),
            'role' => 'supervisor',
            'company_id' => $company->id,
        ]);
        
        $supervisorEmployee = Employee::create([
            'user_id' => $supervisorUser->id,
            'company_id' => $company->id,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $supervisorUser->email,
            'phone_number' => $this->faker->phoneNumber(),
            'weekly_working_hours' => rand(30, 40),
        ]);
        
        $this->command->info("   👥 Created Supervisor: {$supervisorUser->email} for {$location->name}");
        
        // Create 5-12 employees for this location
        $numberOfEmployees = rand(5, 12);
        for ($i = 0; $i < $numberOfEmployees; $i++) {
            $this->createEmployee($company, $location, $supervisorEmployee, $i);
        }
        
        // Create some employees that work in multiple locations (20% chance)
        if (rand(1, 100) <= 20) {
            $this->createMultiLocationEmployee($company, $location, $supervisorEmployee);
        }
        
        $this->command->info("     ✅ Created {$numberOfEmployees} employees for {$location->name}");
    }
    
    private function createEmployee(Company $company, Location $primaryLocation, Employee $supervisor, int $index): Employee
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        
        $user = User::create([
            'name' => "{$firstName} {$lastName}",
            'email' => strtolower("{$firstName}.{$lastName}.{$index}@{$company->slug}.example.com"),
            'email_verified_at' => now(),
            'password' => Hash::make('123456789'),
            'role' => 'employee',
            'company_id' => $company->id,
        ]);
        
        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
            'phone_number' => $this->faker->phoneNumber(),
            'weekly_working_hours' => rand(15, 40),
        ]);
        
        return $employee;
    }
    
    private function createMultiLocationEmployee(Company $company, Location $primaryLocation, Employee $supervisor): void
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        
        $user = User::create([
            'name' => "{$firstName} {$lastName}",
            'email' => strtolower("{$firstName}.{$lastName}.multi@{$company->slug}.example.com"),
            'email_verified_at' => now(),
            'password' => Hash::make('123456789'),
            'role' => 'employee',
            'company_id' => $company->id,
        ]);
        
        Employee::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'supervisor_id' => $supervisor->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
            'phone_number' => $this->faker->phoneNumber(),
            'weekly_working_hours' => 38,
        ]);
        
        $this->command->info("     👥 Created multi-location employee: {$user->email}");
    }
    
    private function getWeeklyHoursForType(string $type): int
    {
        return match($type) {
            'full-time' => rand(35, 40),
            'part-time' => rand(15, 30),
            'casual' => rand(5, 25),
            'contractor' => rand(20, 40),
            default => 20
        };
    }
    
    private function printSummary(): void
    {
        $totalUsers = User::count();
        $totalEmployees = Employee::count();
        $adminCount = User::where('role', 'admin')->count();
        $supervisorCount = User::where('role', 'supervisor')->count();
        $employeeCount = User::where('role', 'employee')->count();
        
        $this->command->info('');
        $this->command->info('📊 SEEDING SUMMARY:');
        $this->command->info("   Total Users: {$totalUsers}");
        $this->command->info("   Total Employee Profiles: {$totalEmployees}");
        $this->command->info("   Admins: {$adminCount}");
        $this->command->info("   Supervisors: {$supervisorCount}");
        $this->command->info("   Employees: {$employeeCount}");
        $this->command->info('');
    }
}