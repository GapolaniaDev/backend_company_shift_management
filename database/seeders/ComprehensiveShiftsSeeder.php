<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Location;
use App\Models\Employee;
use App\Models\ShiftType;
use App\Models\Shift;
use Faker\Factory as Faker;

class ComprehensiveShiftsSeeder extends Seeder
{
    private $faker;
    private $startDate;
    private $endDate;
    
    // Shift time patterns
    private $shiftPatterns = [
        'morning_4h' => ['start' => '06:00', 'end' => '10:00', 'hours' => 4],
        'morning_5h' => ['start' => '06:00', 'end' => '11:00', 'hours' => 5],
        'morning_8h' => ['start' => '06:00', 'end' => '14:00', 'hours' => 8],
        'midday_4h' => ['start' => '12:00', 'end' => '16:00', 'hours' => 4],
        'midday_5h' => ['start' => '12:00', 'end' => '17:00', 'hours' => 5],
        'evening_4h' => ['start' => '18:00', 'end' => '22:00', 'hours' => 4],
        'evening_5h' => ['start' => '18:00', 'end' => '23:00', 'hours' => 5],
        'sunday_5h' => ['start' => '12:00', 'end' => '17:00', 'hours' => 5],
    ];

    public function __construct()
    {
        $this->faker = Faker::create('en_AU');
        $this->startDate = Carbon::create(2025, 7, 1); // July 1, 2025
        $this->endDate = Carbon::create(2026, 6, 30);   // June 30, 2026
    }

    public function run(): void
    {
        $companies = Company::whereIn('slug', ['dimeo', 'corporate-clean', 'bio-green-family', 'adelaide-facility-services'])->get();
        
        $this->command->info('🔄 Creating comprehensive shift schedule...');
        $this->command->info("📅 Period: {$this->startDate->format('M j, Y')} to {$this->endDate->format('M j, Y')}");
        
        $totalShifts = 0;
        
        foreach ($companies as $company) {
            $this->command->info("🏢 Processing {$company->name}...");
            
            $locations = Location::where('company_id', $company->id)->get();
            $shiftTypes = ShiftType::where('company_id', $company->id)->get();
            
            foreach ($locations as $location) {
                // Get employees for this company (since employees don't have location_id)
                $employees = Employee::where('company_id', $company->id)->get();
                
                if ($employees->isEmpty()) continue;
                
                // Assign random employees to this location (simulate working at different locations)
                $employees = $employees->shuffle()->take(rand(5, 12));
                
                $locationShifts = $this->createShiftsForLocation($company, $location, $employees, $shiftTypes);
                $totalShifts += $locationShifts;
                
                $this->command->info("  📍 {$location->name}: {$locationShifts} shifts");
            }
        }
        
        $this->command->info("🎉 Created {$totalShifts} shifts for the full year!");
        $this->printShiftSummary();
    }
    
    private function createShiftsForLocation(Company $company, Location $location, $employees, $shiftTypes): int
    {
        $shiftsCreated = 0;
        $current = $this->startDate->copy();
        
        // Group employees by work pattern
        $mondayToFridayEmployees = $employees->take(ceil($employees->count() * 0.75)); // 75% work M-F
        $fullWeekEmployees = $employees->skip(ceil($employees->count() * 0.75));       // 25% work M-Sun
        
        while ($current->lte($this->endDate)) {
            $dayOfWeek = $current->dayOfWeek;
            $isWeekend = $dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY;
            $isSunday = $dayOfWeek === Carbon::SUNDAY;
            
            // Monday to Friday shifts
            if (!$isWeekend) {
                $shiftsCreated += $this->createDailyShifts($company, $location, $mondayToFridayEmployees, $shiftTypes, $current, false);
                $shiftsCreated += $this->createDailyShifts($company, $location, $fullWeekEmployees, $shiftTypes, $current, false);
            }
            
            // Weekend shifts (only full-week employees)
            if ($isWeekend && !$fullWeekEmployees->isEmpty()) {
                $weekendEmployees = $fullWeekEmployees->shuffle()->take(rand(2, min(5, $fullWeekEmployees->count())));
                $shiftsCreated += $this->createDailyShifts($company, $location, $weekendEmployees, $shiftTypes, $current, $isSunday);
            }
            
            $current->addDay();
        }
        
        return $shiftsCreated;
    }
    
    private function createDailyShifts($company, $location, $employees, $shiftTypes, $date, $isSunday): int
    {
        $shiftsCreated = 0;
        $employeesToSchedule = $employees->shuffle();
        
        // Sunday has different hours (12PM to 5PM)
        if ($isSunday) {
            $pattern = $this->shiftPatterns['sunday_5h'];
            $employeeCount = rand(1, min(3, $employeesToSchedule->count()));
            
            for ($i = 0; $i < $employeeCount; $i++) {
                if (!isset($employeesToSchedule[$i])) break;
                
                $employee = $employeesToSchedule[$i];
                $shiftType = $shiftTypes->random();
                
                $this->createShift($company, $location, $employee, $shiftType, $date, $pattern);
                $shiftsCreated++;
            }
        } else {
            // Regular weekdays - create multiple shift times
            $patterns = ['morning_4h', 'morning_5h', 'morning_8h', 'midday_4h', 'midday_5h', 'evening_4h', 'evening_5h'];
            
            // Randomly select 2-4 shift patterns for the day
            $selectedPatterns = collect($patterns)->shuffle()->take(rand(2, 4));
            
            $employeeIndex = 0;
            foreach ($selectedPatterns as $patternKey) {
                $pattern = $this->shiftPatterns[$patternKey];
                
                // 1-2 employees per shift time
                $employeesForThisShift = rand(1, 2);
                
                for ($j = 0; $j < $employeesForThisShift; $j++) {
                    if ($employeeIndex >= $employeesToSchedule->count()) break;
                    
                    $employee = $employeesToSchedule[$employeeIndex];
                    $shiftType = $shiftTypes->random();
                    
                    $this->createShift($company, $location, $employee, $shiftType, $date, $pattern);
                    $shiftsCreated++;
                    $employeeIndex++;
                }
            }
        }
        
        return $shiftsCreated;
    }
    
    private function createShift($company, $location, $employee, $shiftType, $date, $pattern): void
    {
        $startDateTime = $date->copy()->setTimeFromTimeString($pattern['start']);
        $endDateTime = $date->copy()->setTimeFromTimeString($pattern['end']);
        
        // Add some random variations (±15 minutes)
        if (rand(1, 100) <= 30) { // 30% chance of variation
            $variation = rand(-15, 15);
            $startDateTime->addMinutes($variation);
            $endDateTime->addMinutes($variation);
        }
        
        Shift::create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'shift_type_id' => $shiftType->id,
            'location_id' => $location->id,
            'date_start' => $startDateTime,
            'date_end' => $endDateTime,
            'total_hours' => $pattern['hours'],
            'state' => $this->getRandomState(),
            'comments' => $this->faker->optional(0.3)->sentence(), // 30% chance of notes
        ]);
    }
    
    private function getRandomState(): int
    {
        $states = [
            0 => 70,    // Not started - 70%
            1 => 20,    // Started - 20%
            2 => 10,    // Finished - 10%
        ];
        
        $random = rand(1, 100);
        $cumulative = 0;
        
        foreach ($states as $state => $percentage) {
            $cumulative += $percentage;
            if ($random <= $cumulative) {
                return $state;
            }
        }
        
        return 0; // Default to not started
    }
    
    private function printShiftSummary(): void
    {
        $totalShifts = Shift::count();
        $shiftsByState = [];
        
        $states = [0 => 'not_started', 1 => 'started', 2 => 'finished'];
        foreach ($states as $stateValue => $stateName) {
            $count = Shift::where('state', $stateValue)->count();
            if ($count > 0) {
                $shiftsByState[$stateName] = $count;
            }
        }
        
        $this->command->info('');
        $this->command->info('📊 SHIFT GENERATION SUMMARY:');
        $this->command->info("   Total Shifts: {$totalShifts}");
        $this->command->info("   Period: {$this->startDate->format('M j, Y')} - {$this->endDate->format('M j, Y')} (365 days)");
        
        foreach ($shiftsByState as $state => $count) {
            $percentage = round(($count / $totalShifts) * 100, 1);
            $this->command->info("   {$state}: {$count} ({$percentage}%)");
        }
        
        $averagePerDay = round($totalShifts / 365, 1);
        $this->command->info("   Average shifts per day: {$averagePerDay}");
        $this->command->info('');
    }
}