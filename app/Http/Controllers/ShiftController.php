<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Shift;

class ShiftController extends Controller
{
    private $colorPalettes = [
        'full' => ['1D5A73', '54B5BF', '1F8C45', '97BF41', 'F2E422'],
        'bright' => ['1F8C45', '97BF41', 'F2E422', 'F2F2F2', '0D0D0D'],
        'muted' => ['1D5A73', '54B5BF', '1CA698', '1F8C45', '97BF41'],
        'deep' => ['1D5A73', '1F8C45', '97BF41', 'F2F2F2', '0D0D0D'],
        'dark' => ['1D5A73', '1CA698', '1F8C45', 'F2F2F2', '0D0D0D'],
        'gradient' => ['83ACBE', '3F7C99', 'FFFFFF'],
    ];

    private $backgroundColors = [
        'E6F1F5',
        'C2D4D9',
        'C7E4CE',
        'EAF4DC',
        'F2F2F2',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $today = Carbon::today();

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

    public function getTodayShift(Request $request)
    {
        $user = $request->user();

        $today = Carbon::now()->toDateString();

        $shift = Shift::whereHas('employee', function ($query) use ($user) {
            $query->where('email', $user->email);
        })
            ->whereDate('date_start', '<=', $today)
            ->whereDate('date_end', '>=', $today)
            ->first();

        if ($shift) {
            return response()->json([
                'success' => true,
                'shift' => $shift
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No shift found for today'
            ], 404);
        }
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

    public function updateClock(Request $request, $shiftId = null)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'type' => 'required|in:clock_on,clock_off',
        ]);

        $shift = Shift::findOrFail($shiftId);
        $user = $request->user();

        if ($shift->employee_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        if (!$shift->isWithinRadius($request->lat, $request->lng)) {
            return response()->json(['error' => 'You are outside the allowed radius'], 422);
        }

        if ($request->type === 'clock_on') {
            if ($shift->clock_off_time) {
                return response()->json(['error' => 'Clock on cannot be updated after clock off'], 422);
            }

            $shift->update([
                'clock_on_lat' => $request->lat,
                'clock_on_lng' => $request->lng,
                'clock_on_time' => now(),
            ]);
        } else {
            if (!$shift->clock_on_time) {
                return response()->json(['error' => 'Clock off cannot happen before clock on'], 422);
            }

            $shift->update([
                'clock_off_lat' => $request->lat,
                'clock_off_lng' => $request->lng,
                'clock_off_time' => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Coordinates updated successfully.']);
    }

}
