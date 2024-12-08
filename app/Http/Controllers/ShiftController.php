<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Shift;

class ShiftController extends Controller
{
    // Las paletas de colores
    private $colorPalettes = [
        'full' => ['1D5A73', '54B5BF', '1F8C45', '97BF41', 'F2E422'],
        'bright' => ['1F8C45', '97BF41', 'F2E422', 'F2F2F2', '0D0D0D'],
        'muted' => ['1D5A73', '54B5BF', '1CA698', '1F8C45', '97BF41'],
        'deep' => ['1D5A73', '1F8C45', '97BF41', 'F2F2F2', '0D0D0D'],
        'dark' => ['1D5A73', '1CA698', '1F8C45', 'F2F2F2', '0D0D0D'],
        'gradient' => ['83ACBE', '3F7C99', 'FFFFFF'],
    ];

    private $backgroundColors = [
        'E6F1F5', // Azul claro
        'C2D4D9', // Gris azulado
        'C7E4CE', // Verde claro
        'EAF4DC', // Amarillo claro
        'F2F2F2', // Gris muy claro
    ];


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$today = Carbon::today();
        $date = '2024-09-16';
        $today = new Carbon($date);

        $shifts = Shift::with('employee')
            ->where('date_start', '<=', $today->endOfDay())
            ->get();

        $formattedShifts = $shifts->map(function ($shift) {
            return [
                'id' => $shift->id,
                'first_name' => $shift->employee->first_name,
                'last_name' => $shift->employee->last_name,
                'date_start' => $shift->date_start->format('Y-m-d H:i:s'),
                'date_end' => $shift->date_end->format('Y-m-d H:i:s'),
                'time_start' => $shift->date_start->format('H:i:s'),
                'time_end' => $shift->date_end->format('H:i:s'),
                'time_start_A' => $shift->date_start->format('g:i A'),
                'time_end_A' => $shift->date_end->format('g:i A'),
                'total_hours' => $shift->total_hours,
                'image_profile' => $this->generateProfileTextAndColor($shift->employee->first_name, $shift->employee->last_name),
                'role' => 'cleaner',
            ];
        });

        return response()->json($formattedShifts);
    }

    public function generateProfileTextAndColor($var1, $var2)
    {
        $firstLetter1 = strtoupper($var1[0]);
        $firstLetter2 = strtoupper($var2[0]);
        $textProfile = $firstLetter1 . $firstLetter2;
        $textColor = $this->generateColorForTextProfile($textProfile);
        $backgroundColor = $this->generateBackgroundColorForTextProfile($textProfile);
        return [
            'text_profile' => $textProfile,
            'text_color' => $textColor,
            'background_color' => $backgroundColor,
        ];
    }

    private function generateColorForTextProfile($textProfile)
    {
        $palettes = array_merge(...array_values($this->colorPalettes));
        $hashValue = crc32($textProfile);
        $index = $hashValue % count($palettes);
        return $palettes[$index];
    }

    private function generateBackgroundColorForTextProfile($textProfile)
    {
        $hashValue = crc32($textProfile);
        $index = $hashValue % count($this->backgroundColors);
        return $this->backgroundColors[$index];
    }


}
