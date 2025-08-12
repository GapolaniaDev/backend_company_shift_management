<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
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

        foreach ($employees as $employee) {
            User::create([
                'name' => $employee['name'],
                'email' => $employee['email'],
                'password' => bcrypt('123456789'),
            ]);
        }
    }
}
