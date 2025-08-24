<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class ShiftTypesTableSeeder extends Seeder
{
    public function run()
    {
        // Get company IDs
        $defaultCompany = Company::where('slug', 'default')->first();
        $dimeoCompany = Company::where('slug', 'dimeo')->first();
        $corporateCleanCompany = Company::where('slug', 'corporate-clean')->first();
        $bioGreenCompany = Company::where('slug', 'bio-green-family')->first();
        $adelaideFacilityCompany = Company::where('slug', 'adelaide-facility-services')->first();

        $shiftTypes = [
            // Default Company Shift Types
            [
                'name' => 'Day Shift',
                'description' => 'Standard day cleaning shift',
                'weekly_hours' => 40,
                'schedule' => json_encode([
                    'Monday' => ['start' => '09:00', 'end' => '17:00'],
                    'Tuesday' => ['start' => '09:00', 'end' => '17:00'],
                    'Wednesday' => ['start' => '09:00', 'end' => '17:00'],
                    'Thursday' => ['start' => '09:00', 'end' => '17:00'],
                    'Friday' => ['start' => '09:00', 'end' => '17:00'],
                ]),
                'company_id' => $defaultCompany->id,
            ],
            
            // Dimeo Company Shift Types  
            [
                'name' => 'Morning Commercial Clean',
                'description' => 'Early morning commercial cleaning for Dimeo clients',
                'weekly_hours' => 35,
                'schedule' => json_encode([
                    'Monday' => ['start' => '06:00', 'end' => '13:00'],
                    'Tuesday' => ['start' => '06:00', 'end' => '13:00'],
                    'Wednesday' => ['start' => '06:00', 'end' => '13:00'],
                    'Thursday' => ['start' => '06:00', 'end' => '13:00'],
                    'Friday' => ['start' => '06:00', 'end' => '13:00'],
                ]),
                'company_id' => $dimeoCompany->id,
            ],
            [
                'name' => 'Evening Office Clean',
                'description' => 'Evening office cleaning for Dimeo clients',
                'weekly_hours' => 25,
                'schedule' => json_encode([
                    'Monday' => ['start' => '18:00', 'end' => '23:00'],
                    'Wednesday' => ['start' => '18:00', 'end' => '23:00'],
                    'Friday' => ['start' => '18:00', 'end' => '23:00'],
                ]),
                'company_id' => $dimeoCompany->id,
            ],
            
            // Corporate Clean Company Shift Types
            [
                'name' => 'Adelaide Day Clean',
                'description' => 'Standard day shift for Corporate Clean Adelaide',
                'weekly_hours' => 38,
                'schedule' => json_encode([
                    'Monday' => ['start' => '08:00', 'end' => '16:00'],
                    'Tuesday' => ['start' => '08:00', 'end' => '16:00'],
                    'Wednesday' => ['start' => '08:00', 'end' => '16:00'],
                    'Thursday' => ['start' => '08:00', 'end' => '16:00'],
                    'Friday' => ['start' => '08:00', 'end' => '15:00'],
                ]),
                'company_id' => $corporateCleanCompany->id,
            ],
            [
                'name' => 'Medical Center Clean',
                'description' => 'Specialized cleaning for medical centers',
                'weekly_hours' => 20,
                'schedule' => json_encode([
                    'Monday' => ['start' => '17:00', 'end' => '21:00'],
                    'Wednesday' => ['start' => '17:00', 'end' => '21:00'],
                    'Friday' => ['start' => '17:00', 'end' => '21:00'],
                    'Saturday' => ['start' => '08:00', 'end' => '16:00'],
                ]),
                'company_id' => $corporateCleanCompany->id,
            ],
            
            // Bio Green Family Shift Types
            [
                'name' => 'Eco-Friendly Clean',
                'description' => 'Environmentally conscious cleaning services for Bio Green Family',
                'weekly_hours' => 40,
                'schedule' => json_encode([
                    'Monday' => ['start' => '07:00', 'end' => '15:00'],
                    'Tuesday' => ['start' => '07:00', 'end' => '15:00'],
                    'Wednesday' => ['start' => '07:00', 'end' => '15:00'],
                    'Thursday' => ['start' => '07:00', 'end' => '15:00'],
                    'Friday' => ['start' => '07:00', 'end' => '15:00'],
                ]),
                'company_id' => $bioGreenCompany->id,
            ],
            
            // Adelaide Facility Services Shift Types
            [
                'name' => 'Commercial Facility Clean',
                'description' => 'Comprehensive facility cleaning for Adelaide Facility Services',
                'weekly_hours' => 35,
                'schedule' => json_encode([
                    'Monday' => ['start' => '06:00', 'end' => '13:00'],
                    'Tuesday' => ['start' => '06:00', 'end' => '13:00'],
                    'Wednesday' => ['start' => '06:00', 'end' => '13:00'],
                    'Thursday' => ['start' => '06:00', 'end' => '13:00'],
                    'Friday' => ['start' => '06:00', 'end' => '13:00'],
                ]),
                'company_id' => $adelaideFacilityCompany->id,
            ],
            [
                'name' => 'Weekend Maintenance',
                'description' => 'Weekend facility maintenance and deep cleaning',
                'weekly_hours' => 16,
                'schedule' => json_encode([
                    'Saturday' => ['start' => '08:00', 'end' => '16:00'],
                    'Sunday' => ['start' => '12:00', 'end' => '20:00'],
                ]),
                'company_id' => $adelaideFacilityCompany->id,
            ],
        ];

        foreach ($shiftTypes as $shiftType) {
            // Check if shift type already exists for this company
            $existingShiftType = DB::table('shift_types')
                ->where('name', $shiftType['name'])
                ->where('company_id', $shiftType['company_id'])
                ->first();

            if (!$existingShiftType) {
                DB::table('shift_types')->insert([
                    'name' => $shiftType['name'],
                    'description' => $shiftType['description'],
                    'weekly_hours' => $shiftType['weekly_hours'],
                    'schedule' => $shiftType['schedule'],
                    'company_id' => $shiftType['company_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✅ Shift types created and distributed across companies');
    }
}