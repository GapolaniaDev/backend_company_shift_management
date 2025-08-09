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
        $bioGreenCompany = Company::where('slug', 'bio-green-family')->first();

        // Array de configuraciones de turnos
        $shiftConfigurations = [
            // Default Company configurations
            ['shift_type_id' => 1, 'employee_id' => 6, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 7, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 8, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 9, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 10, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 11, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 12, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 13, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 14, 'company_id' => $defaultCompany->id],
            ['shift_type_id' => 1, 'employee_id' => 15, 'company_id' => $defaultCompany->id],
            
            // Dimeo Company configurations (employees 1-5)
            ['shift_type_id' => 2, 'employee_id' => 1, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 2, 'employee_id' => 2, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 3, 'employee_id' => 3, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 3, 'employee_id' => 4, 'company_id' => $dimeoCompany->id],
            ['shift_type_id' => 2, 'employee_id' => 5, 'company_id' => $dimeoCompany->id],
            
            // Corporate Clean configurations (employees 16-25)
            ['shift_type_id' => 4, 'employee_id' => 16, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 4, 'employee_id' => 17, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 4, 'employee_id' => 18, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 4, 'employee_id' => 19, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 4, 'employee_id' => 20, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 5, 'employee_id' => 21, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 5, 'employee_id' => 22, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 5, 'employee_id' => 23, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 5, 'employee_id' => 24, 'company_id' => $corporateCleanCompany->id],
            ['shift_type_id' => 5, 'employee_id' => 25, 'company_id' => $corporateCleanCompany->id],
            
            // Bio Green Family configurations (employees 26-35)
            ['shift_type_id' => 6, 'employee_id' => 26, 'company_id' => $bioGreenCompany->id],
            ['shift_type_id' => 6, 'employee_id' => 27, 'company_id' => $bioGreenCompany->id],
            ['shift_type_id' => 6, 'employee_id' => 28, 'company_id' => $bioGreenCompany->id],
            ['shift_type_id' => 6, 'employee_id' => 29, 'company_id' => $bioGreenCompany->id],
            ['shift_type_id' => 6, 'employee_id' => 30, 'company_id' => $bioGreenCompany->id],
            ['shift_type_id' => 6, 'employee_id' => 31, 'company_id' => $bioGreenCompany->id],
            ['shift_type_id' => 6, 'employee_id' => 32, 'company_id' => $bioGreenCompany->id],
            ['shift_type_id' => 6, 'employee_id' => 33, 'company_id' => $bioGreenCompany->id],
            ['shift_type_id' => 6, 'employee_id' => 34, 'company_id' => $bioGreenCompany->id],
            ['shift_type_id' => 6, 'employee_id' => 35, 'company_id' => $bioGreenCompany->id],
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

        $this->command->info('✅ Shift configurations created and distributed across companies:');
        $this->command->info('   - Dimeo: 5 configurations');
        $this->command->info('   - Default Company: 10 configurations');
        $this->command->info('   - Corporate Clean: 10 configurations');
        $this->command->info('   - Bio Green Family: 10 configurations');
    }
}