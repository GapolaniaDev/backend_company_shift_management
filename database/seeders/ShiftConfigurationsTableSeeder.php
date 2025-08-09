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
            ['shift_type_id' => 1, 'employee_id' => 1, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 3, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 6, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 9, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 12, 'company_id' => $defaultCompany->id],
            
            // Dimeo Company configurations  
            ['shift_type_id' => 2, 'employee_id' => 2, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 3, 'employee_id' => 4, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 2, 'employee_id' => 7, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 3, 'employee_id' => 10, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 2, 'employee_id' => 13, 'company_id' => $dimeoCompany->id],
            
            // Corporate Clean configurations
            ['shift_type_id' => 4, 'employee_id' => 5, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 5, 'employee_id' => 8, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 4, 'employee_id' => 11, 'company_id' => $corporateCleanCompany->id],
        ];

        foreach ($shiftConfigurations as $config) {
            DB::table('shift_configurations')->insert([
                'shift_type_id' => $config['shift_type_id'],
                'employee_id' => $config['employee_id'], 
                'company_id' => $config['company_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ Shift configurations created and distributed across companies');
    }
}