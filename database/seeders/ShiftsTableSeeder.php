<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ShiftType;

class ShiftsTableSeeder extends Seeder
{
    public function run()
    {
        // Get all companies
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->warn('⚠️ No companies found. Please run companies seeder first.');
            return;
        }

        $shiftsCreated = 0;
        $startDate = \Carbon\Carbon::create(2025, 7, 1);
        $endDate = \Carbon\Carbon::create(2025, 8, 30); // Reduce range for performance

        foreach ($companies as $company) {
            // Get employees for this company
            $employees = Employee::where('company_id', $company->id)->get();
            if ($employees->isEmpty()) {
                $this->command->warn("⚠️ No employees found for company: {$company->name}");
                continue;
            }

            // Get shift types for this company
            $shiftTypes = ShiftType::where('company_id', $company->id)->get();
            if ($shiftTypes->isEmpty()) {
                $this->command->warn("⚠️ No shift types found for company: {$company->name}");
                continue;
            }

            // Generate shifts for 2 months (to avoid performance issues)
            $currentDate = $startDate->copy();
            $companyShifts = 0;

            while ($currentDate->lte($endDate)) {
                $weekday = $currentDate->dayOfWeek; // 0=Sunday, 1=Monday, etc.
                
                // Generate shifts only for weekdays (Mon-Fri)
                if ($weekday >= 1 && $weekday <= 5) {
                    // Select a subset of employees for each day to create shifts
                    $dailyEmployees = $employees->shuffle()->take(min(3, $employees->count()));
                    
                    foreach ($dailyEmployees as $employee) {
                        // Pick a random shift type for variety
                        $shiftType = $shiftTypes->random();
                        
                        // Create morning shift
                        DB::table('shifts')->insert([
                            'shift_type_id' => $shiftType->id,
                            'employee_id' => $employee->id,
                            'date_start' => $currentDate->copy()->setTime(9, 0),
                            'date_end' => $currentDate->copy()->setTime(17, 0),
                            'total_hours' => 8.0,
                            'weekday_code' => $weekday,
                            'state' => 0,
                            'shift_status' => 'published',
                            'company_id' => $company->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $shiftsCreated++;
                        $companyShifts++;
                    }
                }
                
                $currentDate->addDay();
            }

            $this->command->info("✅ Created {$companyShifts} shifts for {$company->name}");
        }

        $this->command->info('✅ Shifts seeding completed:');
        $this->command->info("   Total shifts created: {$shiftsCreated}");
        $this->command->info("   Period: July 1 - August 30, 2025");
    }
}