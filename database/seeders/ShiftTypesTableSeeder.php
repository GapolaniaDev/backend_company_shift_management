<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftTypesTableSeeder extends Seeder
{
    public function run()
    {
        $shiftTypes = [
            'FT Day Cleaner (E.L)' => [
                'Mon' => ['start' => '10:00:00', 'finish' => '14:00:00'],
                'Tue' => ['start' => '10:00:00', 'finish' => '14:00:00'],
                'Wed' => ['start' => '10:00:00', 'finish' => '14:00:00'],
                'Thu' => ['start' => '10:00:00', 'finish' => '14:00:00'],
                'Fri' => ['start' => '10:00:00', 'finish' => '14:00:00'],
            ],
            'FT Day Cleaner (T.M)' => [
                'Mon' => ['start' => '13:00:00', 'finish' => '17:00:00'],
                'Tue' => ['start' => '13:00:00', 'finish' => '17:00:00'],
                'Wed' => ['start' => '13:00:00', 'finish' => '17:00:00'],
                'Thu' => ['start' => '13:00:00', 'finish' => '17:00:00'],
                'Fri' => ['start' => '13:00:00', 'finish' => '17:00:00'],
            ],
            'PT Tenancy Cleaner 1 (P.M)' => [
                'Mon' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Tue' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Wed' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Thu' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Fri' => ['start' => '18:00:00', 'finish' => '22:00:00'],
            ],
            'PT Tenancy Cleaner 2 (K.A)' => [
                'Mon' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Tue' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Wed' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Thu' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Fri' => ['start' => '18:00:00', 'finish' => '22:00:00'],
            ],
            'PT Tenancy Cleaner 3 (J.Z)' => [
                'Mon' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Tue' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Wed' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Thu' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Fri' => ['start' => '18:00:00', 'finish' => '22:00:00'],
            ],
            'PT Tenancy Cleaner 4 (J.J)' => [
                'Mon' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Tue' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Wed' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Thu' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Fri' => ['start' => '18:00:00', 'finish' => '22:00:00'],
            ],
            'PT Tenancy Cleaner 5 (A.B)' => [
                'Mon' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Tue' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Wed' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Thu' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Fri' => ['start' => '18:00:00', 'finish' => '22:00:00'],
            ],
            'PT Tenancy Cleaner 6 (A.D)' => [
                'Mon' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Tue' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Wed' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Thu' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Fri' => ['start' => '18:00:00', 'finish' => '22:00:00'],
            ],
            'PT Nigth Tolilet Cleaner (L.P)' => [
                'Mon' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Tue' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Wed' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Thu' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Fri' => ['start' => '18:00:00', 'finish' => '22:00:00'],
            ],
            'PT Night Supervisor Cleaner (T.C)' => [
                'Mon' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Tue' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Wed' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Thu' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Fri' => ['start' => '18:00:00', 'finish' => '22:00:00'],
            ],
            'PT Replacement Cleaner' => [
                'Mon' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Tue' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Wed' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Thu' => ['start' => '18:00:00', 'finish' => '22:00:00'],
                'Fri' => ['start' => '18:00:00', 'finish' => '22:00:00'],
            ],
        ];

        foreach ($shiftTypes as $type => $schedule) {
            $scheduleJson = json_encode(array_map(function ($times) {
                return [
                    'time_start' => date("H:i:s", strtotime($times['start'])),
                    'time_finish' => date("H:i:s", strtotime($times['finish'])),
                ];
            }, $schedule));

            DB::table('shift_types')->insert([
                'name' => $type,
                'weekly_hours' => 0,
                'schedule' => $scheduleJson,
            ]);
        }
    }
}
