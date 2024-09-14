<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ShiftType;
use App\Models\Employee;
use App\Models\PayPeriod;
use App\Models\ShiftConfiguration;
use Illuminate\Support\Facades\Log;

class ShiftGenerationController extends Controller
{
    public function generateNextFortnightShifts()
    {

        $startDate = PayPeriod::orderBy('end_date', 'desc')->first()->end_date;
        $endDate = $startDate->copy()->addDays(14);

        $shiftConfigurations = ShiftConfiguration::all();
        Log::info('Accediste al método generateNextFortnightShifts del controlador!');
        foreach ($shiftConfigurations as $config) {
            return response()->json([
                $config->shiftType
            ]);
            /*$shiftType = $config->shiftType;
            $employee = $config->employee;*/

            /*$shiftDuration = $config->shift_duration;

            // La lógica para crear turnos
            for ($date = $startDate; $date->lessThan($endDate); $date->addDay()) {
                Shift::create([
                    'shift_type_id' => $shiftType->id,
                    'employee_id' => $employee->id,
                    'date_start' => $date->copy()->setTime(9, 0),  // Ejemplo de hora de inicio
                    'date_end' => $date->copy()->setTime(9, 0)->addHours($shiftDuration),
                    'total_hours' => $shiftDuration,
                    'weekday_code' => $date->format('D'),
                    'comments' => 'Turno generado automáticamente',
                ]);
            }*/
        }

        return response()->json([
            $startDate,
            $endDate,
            $shiftConfigurations
        ]);
    }
}
