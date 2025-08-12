<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
        $employees = [
            ['name' => 'Gustavo Polania', 'email' => 'gapolania0796@gmail.com'],
            ['name' => 'Gustavo Adolfo', 'email' => 'gustav0796@hotmail.com'],
            ['name' => 'Estefania Lopez', 'email' => 'stracke.greyson@example.net'],
            ['name' => 'Tatiana Montoya', 'email' => 'autumn75@example.com'],
            ['name' => 'Paola Molina', 'email' => 'jamaal70@example.net'],
            ['name' => 'Katherine Avila', 'email' => 'oconnell.estrella@example.net'],
            ['name' => 'Juan Carlos Zuleta', 'email' => 'derick.moore@example.com'],
            ['name' => 'Jhon Justo Huaynacho', 'email' => 'tamia.cartwright@example.com'],
            ['name' => 'Andrea Barriga', 'email' => 'nellie.reilly@example.org'],
            ['name' => 'Alejandro Dognibene', 'email' => 'tflatley@example.net'],
            ['name' => 'Laura Palomeque', 'email' => 'wayne.walter@example.com'],
            ['name' => 'Tomas Casallas', 'email' => 'dkrajcik@example.net'],
            ['name' => 'Paula Sanchez', 'email' => 'oreilly.herminio@example.com'],
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $userIdCounter++;

        }
    }
}
