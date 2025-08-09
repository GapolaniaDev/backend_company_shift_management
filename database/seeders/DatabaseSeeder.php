<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // First create companies (including default)
            DefaultCompanySeeder::class,
            CompaniesSeeder::class,
            
            // Then create tenant data
            UserSeeder::class,
            EmployeesTableSeeder::class,
            ShiftTypesTableSeeder::class,
            ShiftConfigurationsTableSeeder::class,
            ShiftsTableSeeder::class,
        ]);
    }
}
