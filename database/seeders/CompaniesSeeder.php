<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Dimeo company
        Company::firstOrCreate(
            ['slug' => 'dimeo'],
            [
                'name' => 'Dimeo',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create Corporate Clean Property Services company
        Company::firstOrCreate(
            ['slug' => 'corporate-clean'],
            [
                'name' => 'Corporate Clean Property Services',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create Bio Green Family company (placeholder since we couldn't get specific info)
        Company::firstOrCreate(
            ['slug' => 'bio-green-family'],
            [
                'name' => 'Bio Green Family Pty Ltd',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('✅ Companies created successfully:');
        $this->command->info('   - Dimeo (slug: dimeo)');
        $this->command->info('   - Corporate Clean Property Services (slug: corporate-clean)');
        $this->command->info('   - Bio Green Family Pty Ltd (slug: bio-green-family)');
    }
}
