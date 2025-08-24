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
            
            // Create locations with real Adelaide addresses
            LocationsSeeder::class,
            
            // Create enhanced user structure
            EnhancedUsersSeeder::class,
            
            // Create shift types for all companies
            ShiftTypesTableSeeder::class,
            
            // Generate comprehensive shifts for full year (Jul 2025 - Jun 2026)
            ComprehensiveShiftsSeeder::class,
            
            // Legacy seeders (can be commented out if not needed)
            // UserSeeder::class,
            // EmployeesTableSeeder::class,
            // ShiftConfigurationsTableSeeder::class,
            // ShiftsTableSeeder::class,
        ]);
    }
}
