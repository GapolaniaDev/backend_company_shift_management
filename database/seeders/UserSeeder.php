<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get default company ID
        $defaultCompany = Company::where('slug', 'default')->first();
        $dimeoCompany = Company::where('slug', 'dimeo')->first();
        $corporateCleanCompany = Company::where('slug', 'corporate-clean')->first();

        $employees = [
            ['name' => 'Gustavo Polania', 'email' => 'gapolania0796@gmail.com', 'role' => 'admin', 'company_id' => $defaultCompany->id],
            ['name' => 'Gustavo Adolfo', 'email' => 'gustav0796@hotmail.com', 'role' => 'admin', 'company_id' => $dimeoCompany->id],
            ['name' => 'Estefania Lopez', 'email' => 'stracke.greyson@example.net', 'role' => 'supervisor', 'company_id' => $defaultCompany->id],
            ['name' => 'Tatiana Montoya', 'email' => 'autumn75@example.com', 'role' => 'employee', 'company_id' => $dimeoCompany->id],
            ['name' => 'Paola Molina', 'email' => 'jamaal70@example.net', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Katherine Avila', 'email' => 'oconnell.estrella@example.net', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            ['name' => 'Juan Carlos Zuleta', 'email' => 'derick.moore@example.com', 'role' => 'supervisor', 'company_id' => $dimeoCompany->id],
            ['name' => 'Jhon Justo Huaynacho', 'email' => 'tamia.cartwright@example.com', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Andrea Barriga', 'email' => 'nellie.reilly@example.org', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            ['name' => 'Alejandro Dognibene', 'email' => 'tflatley@example.net', 'role' => 'employee', 'company_id' => $dimeoCompany->id],
            ['name' => 'Laura Palomeque', 'email' => 'wayne.walter@example.com', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Tomas Casallas', 'email' => 'dkrajcik@example.net', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            ['name' => 'Paula Sanchez', 'email' => 'oreilly.herminio@example.com', 'role' => 'employee', 'company_id' => $dimeoCompany->id]
        ];

        foreach ($employees as $employee) {
            User::create([
                'name' => $employee['name'],
                'email' => $employee['email'],
                'password' => bcrypt('123456789'),
                'role' => $employee['role'],
                'company_id' => $employee['company_id'],
            ]);
        }

        $this->command->info('✅ Users created and distributed across companies');
    }
}
