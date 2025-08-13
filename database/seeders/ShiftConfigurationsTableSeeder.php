<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ShiftType;

class ShiftConfigurationsTableSeeder extends Seeder
{
    public function run()
    {
        // Get companies
        $companies = [
            'default' => Company::where('slug', 'default')->first(),
            'dimeo' => Company::where('slug', 'dimeo')->first(),
            'corporate-clean' => Company::where('slug', 'corporate-clean')->first(),
            'bio-green-family' => Company::where('slug', 'bio-green-family')->first(),
        ];

        $configurations = [];
        $totalConfigurations = 0;

        // Process each company
        foreach ($companies as $companySlug => $company) {
            if (!$company) {
                $this->command->warn("⚠️ Company not found: {$companySlug}");
                continue;
            }

            // Get employees for this company
            $employees = Employee::where('company_id', $company->id)->get();
            
            // Get shift types for this company
            $shiftTypes = ShiftType::where('company_id', $company->id)->get();

            if ($employees->isEmpty()) {
                $this->command->warn("⚠️ No employees found for company: {$company->name}");
                continue;
            }

            if ($shiftTypes->isEmpty()) {
                $this->command->warn("⚠️ No shift types found for company: {$company->name}");
                continue;
            }

            $companyConfigurations = 0;
            
            // Create configurations for each employee
            foreach ($employees as $employee) {
                // Assign a random shift type from the company's shift types
                $randomShiftType = $shiftTypes->random();

                // Check if configuration already exists
                $existingConfig = DB::table('shift_configurations')
                    ->where('employee_id', $employee->id)
                    ->where('shift_type_id', $randomShiftType->id)
                    ->where('company_id', $company->id)
                    ->first();

                if (!$existingConfig) {
                    DB::table('shift_configurations')->insert([
                        'shift_type_id' => $randomShiftType->id,
                        'employee_id' => $employee->id,
                        'company_id' => $company->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $companyConfigurations++;
                }
            }

            $configurations[$company->name] = $companyConfigurations;
            $totalConfigurations += $companyConfigurations;
        }

        $this->command->info('✅ Shift configurations created:');
        foreach ($configurations as $companyName => $count) {
            $this->command->info("   - {$companyName}: {$count} configurations");
        }
        $this->command->info("   Total configurations: {$totalConfigurations}");
    }
}