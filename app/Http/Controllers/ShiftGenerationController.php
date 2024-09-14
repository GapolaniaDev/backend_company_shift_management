<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\ShiftConfiguration;
use App\Models\Shift;
use DateTime;
use DateInterval;
use DatePeriod;
use Carbon\Carbon;

class ShiftGenerationController extends Controller
{

    private $dateTime_start;
    private $dateTime_end;

    private $weekDayCode;

    private $hours;

    public function generateNextFortnightShifts()
    {

        $startDate = PayPeriod::orderBy('end_date', 'desc')->first()->end_date;
        $endDate = $startDate->copy()->addDays(14);

        $shiftConfigurations = ShiftConfiguration::all();
        foreach ($shiftConfigurations as $config) {
            $days = $this->getDaysWithAcronyms($startDate, $endDate);
            foreach ($days as $day) {
                if ($this->compareSchedules($config->shiftType->schedule, $day)) {
                    Shift::create([
                        'shift_type_id' => $config->shiftType->id,
                        'employee_id' => $config->employee->id,
                        'date_start' => $this->dateTime_start,
                        'date_end' => $this->dateTime_end,
                        'total_hours' => $this->hours,
                        'weekday_code' => $this->weekDayCode
                    ]);
                }
            }
        }

        // Retornar una respuesta
        return response()->json([
            'message' => 'Shift created successfully!'
        ], 201);
    }

    private function getDaysWithAcronyms($startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $start->modify('+1 day');
        $end = new DateTime($endDate);
        $end->modify('+1 day');

        $interval = new DateInterval('P1D');
        $datePeriod = new DatePeriod($start, $interval, $end);

        $days = [];
        foreach ($datePeriod as $date) {
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day_acronym' => $date->format('D'),
            ];
        }

        return $days;
    }

    private function compareSchedules(array $schedule, $day)
    {
        $dayAcronym = $day['day_acronym'];
        if (array_key_exists($dayAcronym, $schedule)) {
            $this->dateTime_start = Carbon::parse($day['date'] . ' ' . $schedule[$dayAcronym]['time_start']);
            $this->dateTime_end = Carbon::parse($day['date'] . ' ' . $schedule[$dayAcronym]['time_finish']);
            $this->weekDayCode = $day['day_acronym'];
            $totalMinutes = $this->dateTime_end->diffInMinutes($this->dateTime_start);
            $this->hours = $totalMinutes / 60;
            return true;
        }
        return false;
    }
}
