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
        // Get company IDs
        $defaultCompany = Company::where('slug', 'default')->first();
        $dimeoCompany = Company::where('slug', 'dimeo')->first();
        $corporateCleanCompany = Company::where('slug', 'corporate-clean')->first();
        $bioGreenCompany = Company::where('slug', 'bio-green-family')->first();

        $employees = [
            // Dimeo Company - Solo los especificados
            ['name' => 'Gustavo Adolfo', 'email' => 'gustav0796@hotmail.com', 'role' => 'admin', 'company_id' => $dimeoCompany->id],
            ['name' => 'Juan Carlos Zuleta', 'email' => 'derick.moore@example.com', 'role' => 'supervisor', 'company_id' => $dimeoCompany->id],
            ['name' => 'Tatiana Montoya', 'email' => 'autumn75@example.com', 'role' => 'employee', 'company_id' => $dimeoCompany->id],
            ['name' => 'Alejandro Dognibene', 'email' => 'tflatley@example.net', 'role' => 'employee', 'company_id' => $dimeoCompany->id],
            ['name' => 'Paula Sanchez', 'email' => 'oreilly.herminio@example.com', 'role' => 'employee', 'company_id' => $dimeoCompany->id],
            
            // Default Company - 10 usuarios
            ['name' => 'Gustavo Polania', 'email' => 'gapolania0796@gmail.com', 'role' => 'admin', 'company_id' => $defaultCompany->id],
            ['name' => 'Estefania Lopez', 'email' => 'stracke.greyson@example.net', 'role' => 'supervisor', 'company_id' => $defaultCompany->id],
            ['name' => 'Katherine Avila', 'email' => 'oconnell.estrella@example.net', 'role' => 'supervisor', 'company_id' => $defaultCompany->id],
            ['name' => 'Andrea Barriga', 'email' => 'nellie.reilly@example.org', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            ['name' => 'Tomas Casallas', 'email' => 'dkrajcik@example.net', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            ['name' => 'Carlos Martinez', 'email' => 'carlos.martinez@default.com', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            ['name' => 'Maria Rodriguez', 'email' => 'maria.rodriguez@default.com', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            ['name' => 'David Wilson', 'email' => 'david.wilson@default.com', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            ['name' => 'Sofia Chen', 'email' => 'sofia.chen@default.com', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            ['name' => 'Miguel Santos', 'email' => 'miguel.santos@default.com', 'role' => 'employee', 'company_id' => $defaultCompany->id],
            
            // Corporate Clean Property Services - 10 usuarios
            ['name' => 'Paola Molina', 'email' => 'jamaal70@example.net', 'role' => 'admin', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Jhon Justo Huaynacho', 'email' => 'tamia.cartwright@example.com', 'role' => 'supervisor', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Laura Palomeque', 'email' => 'wayne.walter@example.com', 'role' => 'supervisor', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'James Smith', 'email' => 'james.smith@corporate.com', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Emma Johnson', 'email' => 'emma.johnson@corporate.com', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Oliver Brown', 'email' => 'oliver.brown@corporate.com', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Charlotte Davis', 'email' => 'charlotte.davis@corporate.com', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'William Miller', 'email' => 'william.miller@corporate.com', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Isabella Garcia', 'email' => 'isabella.garcia@corporate.com', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            ['name' => 'Benjamin Wilson', 'email' => 'benjamin.wilson@corporate.com', 'role' => 'employee', 'company_id' => $corporateCleanCompany->id],
            
            // Bio Green Family - 10 usuarios
            ['name' => 'Sarah Thompson', 'email' => 'sarah.thompson@biogreen.com', 'role' => 'admin', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Michael Green', 'email' => 'michael.green@biogreen.com', 'role' => 'supervisor', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Lisa Evans', 'email' => 'lisa.evans@biogreen.com', 'role' => 'supervisor', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Robert Lee', 'email' => 'robert.lee@biogreen.com', 'role' => 'employee', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Jennifer White', 'email' => 'jennifer.white@biogreen.com', 'role' => 'employee', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Matthew Taylor', 'email' => 'matthew.taylor@biogreen.com', 'role' => 'employee', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Amanda Clark', 'email' => 'amanda.clark@biogreen.com', 'role' => 'employee', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Kevin Rodriguez', 'email' => 'kevin.rodriguez@biogreen.com', 'role' => 'employee', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Rachel Martinez', 'email' => 'rachel.martinez@biogreen.com', 'role' => 'employee', 'company_id' => $bioGreenCompany->id],
            ['name' => 'Daniel Anderson', 'email' => 'daniel.anderson@biogreen.com', 'role' => 'employee', 'company_id' => $bioGreenCompany->id]
        ];

        foreach ($employees as $employee) {
            User::firstOrCreate(
                [
                    'email' => $employee['email'],
                    'company_id' => $employee['company_id']
                ],
                [
                    'name' => $employee['name'],
                    'password' => bcrypt('123456789'),
                    'role' => $employee['role'],
                ]
            );
        }

        $this->command->info('✅ Users created and distributed across companies:');
        $this->command->info('   - Dimeo: 5 users');
        $this->command->info('   - Default Company: 10 users');
        $this->command->info('   - Corporate Clean: 10 users');
        $this->command->info('   - Bio Green Family: 10 users');
    }
}
