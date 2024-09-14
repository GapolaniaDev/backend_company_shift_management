<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            'Estefania Lopez',
            'Tatiana Montoya',
            'Paola Molina',
            'Katherine Avila',
            'Juan Carlos Zuleta',
            'Jhon Justo Huaynacho',
            'Andrea Barriga',
            'Alejandro Dognibene',
            'Laura Palomeque',
            'Tomas Casallas',
            'Paula Sanchez',
        ];

        foreach ($employees as $employee) {
            $names = explode(' ', $employee);
            $firstName = array_shift($names);
            $lastName = implode(' ', $names);

            DB::table('employees')->insert([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $faker->unique()->safeEmail,
                'phone_number' => $faker->phoneNumber,
                'address' => $faker->address,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
