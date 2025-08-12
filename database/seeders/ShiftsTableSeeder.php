<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftsTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Range of employees.
        $employees = range(1, 11);

        // Working days: Monday (1) to Friday (5).
        $workDays = [1, 2, 3, 4, 5, 6, 7];

        // Start and end dates.
        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::create(2025, 7, 31);

        // Bounding box for Adelaide, Postcode 5000 (latitude and longitude range)
        $adelaideBounds = [
            'min_lat' => -34.9331,
            'max_lat' => -34.9202,
            'min_lng' => 138.5937,
            'max_lng' => 138.6135,
        ];

        // Iterate through each day within the date range.
        while ($startDate->lte($endDate)) {
            // Check if the current day is a working day (Monday-Friday).
            if (in_array($startDate->dayOfWeek, $workDays)) {
                foreach ($employees as $employeeId) {
                    // Generate shift hours (between 4 and 8).
                    $hours = $faker->randomFloat(2, 4, 8);

                    // Random start time between 8am and 12pm.
                    $startTime = $faker->dateTimeBetween('08:00:00', '12:00:00');
                    $startTime = Carbon::instance($startTime)->setDate(
                        $startDate->year,
                        $startDate->month,
                        $startDate->day
                    );

                    // Calculate end time based on shift hours.
                    $endTime = (clone $startTime)->addHours((int) $hours)->addMinutes(($hours - (int) $hours) * 60);

                    // Generate random location within Adelaide bounds.
                    $locationLat = $faker->randomFloat(15, $adelaideBounds['min_lat'], $adelaideBounds['max_lat']);
                    $locationLng = $faker->randomFloat(15, $adelaideBounds['min_lng'], $adelaideBounds['max_lng']);

                    // Insert the shift into the database.
                    DB::table('shifts')->insert([
                        'shift_type_id' => 1, // Adjust according to your logic.
                        'employee_id' => $employeeId,
                        'date_start' => $startTime,
                        'date_end' => $endTime,
                        'date_start_timezone' => 'Australia/Adelaide', // Timezone para Adelaide
                        'date_end_timezone' => 'Australia/Adelaide', // Timezone para Adelaide
                        'total_hours' => $hours,
                        'weekday_code' => $startDate->format('D'),
                        'comments' => $faker->sentence(),
                        'replacement_id' => null,
                        'created_at' => now(),
                        'location_lat' => $locationLat, // Added field
                        'location_lng' => $locationLng, // Added field
                        'radius' => '100',
                        'zoom' => '15',
                    ]);
                }
            }

            // Increment to the next day.
            $startDate->addDay();
        }
    }
}
