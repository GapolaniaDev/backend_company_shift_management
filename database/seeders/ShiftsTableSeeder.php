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
        $bioGreenCompany = Company::where('slug', 'bio-green-family')->first();

        // Generate shifts from July 1, 2025 to June 30, 2026
        $startDate = \Carbon\Carbon::create(2025, 7, 1);
        $endDate = \Carbon\Carbon::create(2026, 6, 30);
        
        $shiftsCreated = 0;
        
        // Generate shifts for each company for the full year
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $weekday = $currentDate->dayOfWeek; // 0=Sunday, 1=Monday, etc.
            
            // Default Company - Day shifts (Mon-Fri)
            if ($weekday >= 1 && $weekday <= 5) {
                for ($employeeId = 6; $employeeId <= 15; $employeeId += 2) { // Every 2nd employee
                    DB::table('shifts')->insert([
                        'shift_type_id' => 1,
                        'employee_id' => $employeeId,
                        'date_start' => $currentDate->copy()->setTime(9, 0),
                        'date_end' => $currentDate->copy()->setTime(17, 0),
                        'total_hours' => 8.0,
                        'weekday_code' => $weekday,
                        'state' => 0,
                        'company_id' => $defaultCompany->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $shiftsCreated++;
                }
            }
            
            // Dimeo Company - Morning shifts (Mon-Fri)
            if ($weekday >= 1 && $weekday <= 5) {
                for ($employeeId = 1; $employeeId <= 5; $employeeId++) {
                    DB::table('shifts')->insert([
                        'shift_type_id' => 2,
                        'employee_id' => $employeeId,
                        'date_start' => $currentDate->copy()->setTime(6, 0),
                        'date_end' => $currentDate->copy()->setTime(13, 0),
                        'total_hours' => 7.0,
                        'weekday_code' => $weekday,
                        'state' => 0,
                        'company_id' => $dimeoCompany->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $shiftsCreated++;
                }
            }
            
            // Evening shifts for Dimeo (Mon, Wed, Fri)
            if (in_array($weekday, [1, 3, 5])) {
                DB::table('shifts')->insert([
                    'shift_type_id' => 3,
                    'employee_id' => rand(1, 5),
                    'date_start' => $currentDate->copy()->setTime(18, 0),
                    'date_end' => $currentDate->copy()->setTime(23, 0),
                    'total_hours' => 5.0,
                    'weekday_code' => $weekday,
                    'state' => 0,
                    'company_id' => $dimeoCompany->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $shiftsCreated++;
            }
            
            // Corporate Clean - Day shifts (Mon-Fri)
            if ($weekday >= 1 && $weekday <= 5) {
                for ($employeeId = 16; $employeeId <= 25; $employeeId += 2) { // Every 2nd employee
                    DB::table('shifts')->insert([
                        'shift_type_id' => 4,
                        'employee_id' => $employeeId,
                        'date_start' => $currentDate->copy()->setTime(8, 0),
                        'date_end' => $currentDate->copy()->setTime(16, 0),
                        'total_hours' => 8.0,
                        'weekday_code' => $weekday,
                        'state' => 0,
                        'company_id' => $corporateCleanCompany->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $shiftsCreated++;
                }
            }
            
            // Medical Center Clean for Corporate Clean (Mon, Wed, Fri, Sat)
            if (in_array($weekday, [1, 3, 5, 6])) {
                $startTime = $weekday == 6 ? 8 : 17; // Saturday morning, other days evening
                $endTime = $weekday == 6 ? 16 : 21;
                $hours = $weekday == 6 ? 8 : 4;
                
                DB::table('shifts')->insert([
                    'shift_type_id' => 5,
                    'employee_id' => rand(16, 25),
                    'date_start' => $currentDate->copy()->setTime($startTime, 0),
                    'date_end' => $currentDate->copy()->setTime($endTime, 0),
                    'total_hours' => $hours,
                    'weekday_code' => $weekday,
                    'state' => 0,
                    'company_id' => $corporateCleanCompany->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $shiftsCreated++;
            }
            
            // Bio Green Family - Eco-friendly shifts (Mon-Fri)
            if ($weekday >= 1 && $weekday <= 5) {
                for ($employeeId = 26; $employeeId <= 35; $employeeId += 3) { // Every 3rd employee
                    DB::table('shifts')->insert([
                        'shift_type_id' => 6, // Assumiendo un shift type para Bio Green
                        'employee_id' => $employeeId,
                        'date_start' => $currentDate->copy()->setTime(7, 0),
                        'date_end' => $currentDate->copy()->setTime(15, 0),
                        'total_hours' => 8.0,
                        'weekday_code' => $weekday,
                        'state' => 0,
                        'company_id' => $bioGreenCompany->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $shiftsCreated++;
                }
            }
            
            $currentDate->addDay();
        }

        $this->command->info('✅ Shifts created for July 2025 - June 2026:');
        $this->command->info("   Total shifts created: {$shiftsCreated}");
        $this->command->info('   Period: July 1, 2025 to June 30, 2026');
    }
}