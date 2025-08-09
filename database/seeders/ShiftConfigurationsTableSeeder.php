<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class ShiftConfigurationsTableSeeder extends Seeder
{
    public function run()
    {
        // Get company IDs
        $defaultCompany = Company::where('slug', 'default')->first();
        $dimeoCompany = Company::where('slug', 'dimeo')->first();
        $corporateCleanCompany = Company::where('slug', 'corporate-clean')->first();

        // Array de configuraciones de turnos
        $shiftConfigurations = [
            // Default Company configurations
            ['shift_type_id' => 1, 'employee_id' => 1, 'shift_duration' => 8, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 3, 'shift_duration' => 8, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 6, 'shift_duration' => 8, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 9, 'shift_duration' => 8, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 12, 'shift_duration' => 8, 'company_id' => $defaultCompany->id],
            
            // Dimeo Company configurations  
            ['shift_type_id' => 2, 'employee_id' => 2, 'shift_duration' => 7, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 3, 'employee_id' => 4, 'shift_duration' => 5, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 2, 'employee_id' => 7, 'shift_duration' => 7, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 3, 'employee_id' => 10, 'shift_duration' => 5, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 2, 'employee_id' => 13, 'shift_duration' => 7, 'company_id' => $dimeoCompany->id],
            
            // Corporate Clean configurations
            ['shift_type_id' => 4, 'employee_id' => 5, 'shift_duration' => 8, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 5, 'employee_id' => 8, 'shift_duration' => 4, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 4, 'employee_id' => 11, 'shift_duration' => 8, 'company_id' => $corporateCleanCompany->id],
        ];

        foreach ($shiftConfigurations as $config) {
            DB::table('shift_configurations')->insert([
                'shift_type_id' => $config['shift_type_id'],
                'employee_id' => $config['employee_id'], 
                'shift_duration' => $config['shift_duration'],
                'company_id' => $config['company_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ Shift configurations created and distributed across companies');
    }
}