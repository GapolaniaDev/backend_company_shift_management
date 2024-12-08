<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftConfigurationsTableSeeder extends Seeder
{
    public function run()
    {
        // Array de configuraciones de turnos
        $shiftConfigurations = [
            ['shift_type_id' => 1, 'employee_id' => 1],
            ['shift_type_id' => 2, 'employee_id' => 2],
            ['shift_type_id' => 3, 'employee_id' => 3],
            ['shift_type_id' => 4, 'employee_id' => 4],
            ['shift_type_id' => 5, 'employee_id' => 5],
            ['shift_type_id' => 6, 'employee_id' => 6],
            ['shift_type_id' => 7, 'employee_id' => 7],
            ['shift_type_id' => 8, 'employee_id' => 8],
            ['shift_type_id' => 9, 'employee_id' => 9],
            ['shift_type_id' => 10, 'employee_id' => 10],
            ['shift_type_id' => 11, 'employee_id' => 11],
        ];

        // Insertar configuraciones en la base de datos
        foreach ($shiftConfigurations as $config) {
            DB::table('shift_configurations')->insert($config);
        }
    }
}
