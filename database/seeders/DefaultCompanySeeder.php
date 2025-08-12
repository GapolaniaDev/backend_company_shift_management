<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default company
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Default Company',
            'slug' => 'default',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update existing records to belong to default company
        DB::table('users')->whereNull('company_id')->update(['company_id' => $companyId]);
        DB::table('employees')->whereNull('company_id')->update(['company_id' => $companyId]);
        DB::table('shift_types')->whereNull('company_id')->update(['company_id' => $companyId]);
        DB::table('shift_configurations')->whereNull('company_id')->update(['company_id' => $companyId]);
        DB::table('shifts')->whereNull('company_id')->update(['company_id' => $companyId]);
        DB::table('pay_periods')->whereNull('company_id')->update(['company_id' => $companyId]);
    }
}
