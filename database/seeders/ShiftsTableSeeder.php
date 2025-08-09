<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class ShiftsTableSeeder extends Seeder
{
    public function run()
    {
        // Get company IDs
        $defaultCompany = Company::where('slug', 'default')->first();
        $dimeoCompany = Company::where('slug', 'dimeo')->first();
        $corporateCleanCompany = Company::where('slug', 'corporate-clean')->first();

        // Generate some sample shifts for the next week
        $startDate = now()->startOfWeek();
        
        $shifts = [
            // Default Company shifts
            [
                'shift_type_id' => 1,
                'employee_id' => 1,
                'date_start' => $startDate->copy()->setTime(9, 0),
                'date_end' => $startDate->copy()->setTime(17, 0),
                'total_hours' => 8.0,
                'weekday_code' => 1, // Monday
                'company_id' => $defaultCompany->id,
            ],
            [
                'shift_type_id' => 1,
                'employee_id' => 3,
                'date_start' => $startDate->copy()->addDay()->setTime(9, 0),
                'date_end' => $startDate->copy()->addDay()->setTime(17, 0),
                'total_hours' => 8.0,
                'weekday_code' => 2, // Tuesday
                'company_id' => $defaultCompany->id,
            ],
            
            // Dimeo Company shifts
            [
                'shift_type_id' => 2,
                'employee_id' => 2,
                'date_start' => $startDate->copy()->setTime(6, 0),
                'date_end' => $startDate->copy()->setTime(13, 0),
                'total_hours' => 7.0,
                'weekday_code' => 1, // Monday
                'company_id' => $dimeoCompany->id,
            ],
            [
                'shift_type_id' => 3,
                'employee_id' => 4,
                'date_start' => $startDate->copy()->setTime(18, 0),
                'date_end' => $startDate->copy()->setTime(23, 0),
                'total_hours' => 5.0,
                'weekday_code' => 1, // Monday
                'company_id' => $dimeoCompany->id,
            ],
            
            // Corporate Clean shifts
            [
                'shift_type_id' => 4,
                'employee_id' => 5,
                'date_start' => $startDate->copy()->setTime(8, 0),
                'date_end' => $startDate->copy()->setTime(16, 0),
                'total_hours' => 8.0,
                'weekday_code' => 1, // Monday
                'company_id' => $corporateCleanCompany->id,
            ],
            [
                'shift_type_id' => 5,
                'employee_id' => 8,
                'date_start' => $startDate->copy()->setTime(17, 0),
                'date_end' => $startDate->copy()->setTime(21, 0),
                'total_hours' => 4.0,
                'weekday_code' => 1, // Monday
                'company_id' => $corporateCleanCompany->id,
            ],
        ];

        foreach ($shifts as $shift) {
            DB::table('shifts')->insert([
                'shift_type_id' => $shift['shift_type_id'],
                'employee_id' => $shift['employee_id'],
                'date_start' => $shift['date_start'],
                'date_end' => $shift['date_end'],
                'total_hours' => $shift['total_hours'],
                'weekday_code' => $shift['weekday_code'],
                'state' => 0, // not started
                'company_id' => $shift['company_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ Sample shifts created and distributed across companies');
    }
}