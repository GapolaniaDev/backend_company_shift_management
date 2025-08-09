<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class EmployeesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        
        // Get company IDs
        $defaultCompany = Company::where('slug', 'default')->first();
        $dimeoCompany = Company::where('slug', 'dimeo')->first();
        $corporateCleanCompany = Company::where('slug', 'corporate-clean')->first();
        
        $employees = [
            ['name' => 'Gustavo Polania', 'email' => 'gapolania0796@gmail.com', 'company_id' => $defaultCompany->id],
            ['name' => 'Gustavo Adolfo', 'email' => 'gustav0796@hotmail.com', 'company_id' => $dimeoCompany->id],
            ['name' => 'Estefania Lopez', 'email' => 'stracke.greyson@example.net', 'company_id' => $defaultCompany->id],
            ['name' => 'Tatiana Montoya', 'email' => 'autumn75@example.com', 'company_id' => $dimeoCompany->id],
            ['name' => 'Paola Molina', 'email' => 'jamaal70@example.net', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Katherine Avila', 'email' => 'oconnell.estrella@example.net', 'company_id' => $defaultCompany->id],
            ['name' => 'Juan Carlos Zuleta', 'email' => 'derick.moore@example.com', 'company_id' => $dimeoCompany->id],
            ['name' => 'Jhon Justo Huaynacho', 'email' => 'tamia.cartwright@example.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Andrea Barriga', 'email' => 'nellie.reilly@example.org', 'company_id' => $defaultCompany->id],
            ['name' => 'Alejandro Dognibene', 'email' => 'tflatley@example.net', 'company_id' => $dimeoCompany->id],
            ['name' => 'Laura Palomeque', 'email' => 'wayne.walter@example.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Tomas Casallas', 'email' => 'dkrajcik@example.net', 'company_id' => $defaultCompany->id],
            ['name' => 'Paula Sanchez', 'email' => 'oreilly.herminio@example.com', 'company_id' => $dimeoCompany->id]
        ];
        
        $userIdCounter = 1;
        $supervisorCode = 1;
        foreach ($employees as $employee) {
            $names = explode(' ', $employee['name']);
            $firstName = array_shift($names);
            $lastName = implode(' ', $names);

            DB::table('employees')->insert([
                'user_id' => $userIdCounter,
                'supervisor_id' => $supervisorCode,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $employee['email'],
                'phone_number' => $faker->phoneNumber,
                'address' => $faker->address,
                'company_id' => $employee['company_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $userIdCounter++;
        }

        $this->command->info('✅ Employees created and distributed across companies');
    }
}
