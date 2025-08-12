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
        $bioGreenCompany = Company::where('slug', 'bio-green-family')->first();
        
        $employees = [
            // Dimeo Company - 5 empleados
            ['name' => 'Gustavo Adolfo', 'email' => 'gustav0796@hotmail.com', 'company_id' => $dimeoCompany->id],
            ['name' => 'Juan Carlos Zuleta', 'email' => 'derick.moore@example.com', 'company_id' => $dimeoCompany->id],
            ['name' => 'Tatiana Montoya', 'email' => 'autumn75@example.com', 'company_id' => $dimeoCompany->id],
            ['name' => 'Alejandro Dognibene', 'email' => 'tflatley@example.net', 'company_id' => $dimeoCompany->id],
            ['name' => 'Paula Sanchez', 'email' => 'oreilly.herminio@example.com', 'company_id' => $dimeoCompany->id],
            
            // Default Company - 10 empleados
            ['name' => 'Gustavo Polania', 'email' => 'gapolania0796@gmail.com', 'company_id' => $defaultCompany->id],
            ['name' => 'Estefania Lopez', 'email' => 'stracke.greyson@example.net', 'company_id' => $defaultCompany->id],
            ['name' => 'Katherine Avila', 'email' => 'oconnell.estrella@example.net', 'company_id' => $defaultCompany->id],
            ['name' => 'Andrea Barriga', 'email' => 'nellie.reilly@example.org', 'company_id' => $defaultCompany->id],
            ['name' => 'Tomas Casallas', 'email' => 'dkrajcik@example.net', 'company_id' => $defaultCompany->id],
            ['name' => 'Carlos Martinez', 'email' => 'carlos.martinez@default.com', 'company_id' => $defaultCompany->id],
            ['name' => 'Maria Rodriguez', 'email' => 'maria.rodriguez@default.com', 'company_id' => $defaultCompany->id],
            ['name' => 'David Wilson', 'email' => 'david.wilson@default.com', 'company_id' => $defaultCompany->id],
            ['name' => 'Sofia Chen', 'email' => 'sofia.chen@default.com', 'company_id' => $defaultCompany->id],
            ['name' => 'Miguel Santos', 'email' => 'miguel.santos@default.com', 'company_id' => $defaultCompany->id],
            
            // Corporate Clean Property Services - 10 empleados
            ['name' => 'Paola Molina', 'email' => 'jamaal70@example.net', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Jhon Justo Huaynacho', 'email' => 'tamia.cartwright@example.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Laura Palomeque', 'email' => 'wayne.walter@example.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'James Smith', 'email' => 'james.smith@corporate.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Emma Johnson', 'email' => 'emma.johnson@corporate.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Oliver Brown', 'email' => 'oliver.brown@corporate.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Charlotte Davis', 'email' => 'charlotte.davis@corporate.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'William Miller', 'email' => 'william.miller@corporate.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Isabella Garcia', 'email' => 'isabella.garcia@corporate.com', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Benjamin Wilson', 'email' => 'benjamin.wilson@corporate.com', 'company_id' => $corporateCleanCompany->id],
            
            // Bio Green Family - 10 empleados
            ['name' => 'Sarah Thompson', 'email' => 'sarah.thompson@biogreen.com', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Michael Green', 'email' => 'michael.green@biogreen.com', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Lisa Evans', 'email' => 'lisa.evans@biogreen.com', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Robert Lee', 'email' => 'robert.lee@biogreen.com', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Jennifer White', 'email' => 'jennifer.white@biogreen.com', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Matthew Taylor', 'email' => 'matthew.taylor@biogreen.com', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Amanda Clark', 'email' => 'amanda.clark@biogreen.com', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Kevin Rodriguez', 'email' => 'kevin.rodriguez@biogreen.com', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Rachel Martinez', 'email' => 'rachel.martinez@biogreen.com', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Daniel Anderson', 'email' => 'daniel.anderson@biogreen.com', 'company_id' => $bioGreenCompany->id]
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

        $this->command->info('✅ Employees created and distributed across companies:');
        $this->command->info('   - Dimeo: 5 employees');
        $this->command->info('   - Default Company: 10 employees');
        $this->command->info('   - Corporate Clean: 10 employees');
        $this->command->info('   - Bio Green Family: 10 employees');
    }
}
