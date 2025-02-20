<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftsTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Rango de empleados.
        $employees = range(1, 11);

        // Días laborales: Lunes (1) a Viernes (5).
        $workDays = [1, 2, 3, 4, 5];

        // Fechas de inicio y fin.
        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::create(2025, 7, 31);

        // Bucle por cada día dentro del rango de fechas.
        while ($startDate->lte($endDate)) {
            // Verificar si el día actual es laboral (Lunes-Viernes).
            if (in_array($startDate->dayOfWeek, $workDays)) {
                foreach ($employees as $employeeId) {
                    // Generar horas del turno (entre 4 y 8).
                    $hours = $faker->randomFloat(2, 4, 8);

                    // Hora de inicio aleatoria entre las 8am y 12pm.
                    $startTime = $faker->dateTimeBetween('08:00:00', '12:00:00');
                    $startTime = Carbon::instance($startTime)->setDate(
                        $startDate->year,
                        $startDate->month,
                        $startDate->day
                    );

                    // Hora de finalización basada en las horas trabajadas.
                    $endTime = (clone $startTime)->addHours((int)$hours)->addMinutes(($hours - (int)$hours) * 60);

                    // Insertar el turno en la base de datos.
                    DB::table('shifts')->insert([
                        'shift_type_id' => 1, // Puedes ajustar según tu lógica.
                        'employee_id' => $employeeId,
                        'date_start' => $startTime,
                        'date_end' => $endTime,
                        'total_hours' => $hours,
                        'weekday_code' => $startDate->format('D'),
                        'comments' => $faker->sentence(),
                        'replacement_id' => null,
                        'created_at' => now(),
                    ]);
                }
            }

            // Incrementar al siguiente día.
            $startDate->addDay();
        }
    }
}
