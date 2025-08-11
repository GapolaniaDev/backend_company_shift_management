<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Migrate location data from shifts to locations table
        $this->migrateLocationsFromShifts();
        
        // 2. Create shift assignments for existing shifts
        $this->createShiftAssignments();
        
        // 3. Handle existing replacements
        $this->handleExistingReplacements();
        
        // 4. Create default schedule run for existing shifts
        $this->createDefaultScheduleRun();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear migrated data (will be recreated from original shift data if needed)
        DB::table('shift_assignments')->truncate();
        DB::table('schedule_runs')->truncate();
        DB::table('locations')->truncate();
    }
    
    private function migrateLocationsFromShifts(): void
    {
        // Get unique locations from existing shifts (only lat/lng available)
        $uniqueLocations = DB::table('shifts')
            ->select('location_lat', 'location_lng', 'radius', 'zoom')
            ->whereNotNull('location_lat')
            ->whereNotNull('location_lng')
            ->groupBy('location_lat', 'location_lng', 'radius', 'zoom')
            ->get();
            
        $locationMapping = [];
        
        foreach ($uniqueLocations as $location) {
            // Get company_id from the first shift with this location
            // Check if shifts.company_id exists, otherwise get from employee relation
            $companyId = null;
            if (Schema::hasColumn('shifts', 'company_id')) {
                $companyId = DB::table('shifts')
                    ->where('shifts.location_lat', $location->location_lat)
                    ->where('shifts.location_lng', $location->location_lng)
                    ->value('company_id');
            }
            
            if (!$companyId) {
                $companyId = DB::table('shifts')
                    ->whereNotNull('employee_id')
                    ->join('employees', 'shifts.employee_id', '=', 'employees.id')
                    ->join('users', 'employees.user_id', '=', 'users.id')
                    ->where('shifts.location_lat', $location->location_lat)
                    ->where('shifts.location_lng', $location->location_lng)
                    ->value('users.id');
            }
                
            if ($companyId) {
                $locationId = DB::table('locations')->insertGetId([
                    'company_id' => $companyId,
                    'name' => 'Location ' . $location->location_lat . ',' . $location->location_lng,
                    'latitude' => $location->location_lat,
                    'longitude' => $location->location_lng,
                    'radius' => $location->radius ?: 100,
                    'zoom' => $location->zoom ?: 16,
                    'timezone' => 'UTC', // Default, can be updated later
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $locationMapping["{$location->location_lat}_{$location->location_lng}"] = $locationId;
            }
        }
        
        // Update shifts with location_id
        foreach ($locationMapping as $key => $locationId) {
            $parts = explode('_', $key);
            $lat = $parts[0] ?? null;
            $lng = $parts[1] ?? null;
            
            DB::table('shifts')
                ->where('location_lat', $lat)
                ->where('location_lng', $lng)
                ->update(['location_id' => $locationId]);
        }
    }
    
    private function createShiftAssignments(): void
    {
        // Create shift_assignments for all existing shifts with employee_id (if not already exists)
        $shifts = DB::table('shifts')->whereNotNull('employee_id')->get();
        
        foreach ($shifts as $shift) {
            // Check if assignment already exists
            $existingAssignment = DB::table('shift_assignments')
                ->where('shift_id', $shift->id)
                ->where('employee_id', $shift->employee_id)
                ->where('assignment_type', 'primary')
                ->first();
                
            if (!$existingAssignment) {
                DB::table('shift_assignments')->insert([
                    'shift_id' => $shift->id,
                    'employee_id' => $shift->employee_id,
                    'status' => $shift->state == 2 ? 'completed' : 'assigned',
                    'assignment_type' => 'primary',
                    'priority' => 1,
                    'assigned_at' => $shift->created_at,
                    'completed_at' => $shift->state == 2 ? $shift->clock_off_time : null,
                    'created_at' => $shift->created_at,
                    'updated_at' => $shift->updated_at,
                ]);
            }
        }
    }
    
    private function handleExistingReplacements(): void
    {
        // Handle existing replacement_id relationships
        $shiftsWithReplacements = DB::table('shifts')->whereNotNull('replacement_id')->get();
        
        foreach ($shiftsWithReplacements as $shift) {
            // Find the primary assignment for this shift
            $primaryAssignment = DB::table('shift_assignments')
                ->where('shift_id', $shift->id)
                ->where('assignment_type', 'primary')
                ->first();
                
            if ($primaryAssignment) {
                // Create replacement assignment
                $replacementAssignmentId = DB::table('shift_assignments')->insertGetId([
                    'shift_id' => $shift->id,
                    'employee_id' => $shift->replacement_id,
                    'status' => $shift->state == 2 ? 'completed' : 'assigned',
                    'assignment_type' => 'replacement',
                    'priority' => 2,
                    'replaces_assignment_id' => $primaryAssignment->id,
                    'assigned_at' => $shift->created_at,
                    'completed_at' => $shift->state == 2 ? $shift->clock_off_time : null,
                    'notes' => 'Migrated from legacy replacement_id field',
                    'created_at' => $shift->created_at,
                    'updated_at' => $shift->updated_at,
                ]);
                
                // Update the primary assignment to show it was replaced
                DB::table('shift_assignments')
                    ->where('id', $primaryAssignment->id)
                    ->update([
                        'replaced_by_assignment_id' => $replacementAssignmentId,
                        'status' => 'completed',
                    ]);
            }
        }
    }
    
    private function createDefaultScheduleRun(): void
    {
        // Group existing shifts by company and create schedule runs for each week
        $companyShifts = collect();
        
        if (Schema::hasColumn('shifts', 'company_id')) {
            $companyShifts = DB::table('shifts')
                ->select('company_id', 'shifts.*')
                ->whereNotNull('company_id')
                ->orderBy('date_start')
                ->get()
                ->groupBy('company_id');
        } else {
            $companyShifts = DB::table('shifts')
                ->join('employees', 'shifts.employee_id', '=', 'employees.id')
                ->join('users', 'employees.user_id', '=', 'users.id')
                ->select('users.id as company_id', 'shifts.*')
                ->whereNotNull('shifts.employee_id')
                ->orderBy('date_start')
                ->get()
                ->groupBy('company_id');
        }
            
        foreach ($companyShifts as $companyId => $shifts) {
            // Group shifts by week
            $weekGroups = $shifts->groupBy(function ($shift) {
                return date('Y-W', strtotime($shift->date_start));
            });
            
            foreach ($weekGroups as $week => $weekShifts) {
                $firstShift = $weekShifts->first();
                $lastShift = $weekShifts->last();
                
                $periodStart = date('Y-m-d', strtotime('monday this week', strtotime($firstShift->date_start)));
                $periodEnd = date('Y-m-d', strtotime('sunday this week', strtotime($lastShift->date_start)));
                
                // Check if schedule run already exists for this period
                $existingRun = DB::table('schedule_runs')
                    ->where('company_id', $companyId)
                    ->where('period_start', $periodStart)
                    ->where('period_end', $periodEnd)
                    ->first();
                    
                if ($existingRun) {
                    $scheduleRunId = $existingRun->id;
                } else {
                    $scheduleRunId = DB::table('schedule_runs')->insertGetId([
                        'company_id' => $companyId,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'period_name' => "Week $week (Migrated)",
                        'status' => 'published', // Existing shifts are considered published
                        'generated_at' => $firstShift->created_at,
                        'published_at' => $firstShift->created_at,
                        'generated_by' => $companyId, // Use company user as generator
                        'published_by' => $companyId,
                        'generation_summary' => json_encode([
                            'shifts_migrated' => $weekShifts->count(),
                            'migration_date' => now()->toDateTimeString(),
                        ]),
                        'notes' => 'Automatically created during data migration from legacy system',
                        'created_at' => $firstShift->created_at,
                        'updated_at' => now(),
                    ]);
                }
                
                // Update all shifts in this week with the schedule_run_id
                $shiftIds = $weekShifts->pluck('id')->toArray();
                DB::table('shifts')
                    ->whereIn('id', $shiftIds)
                    ->update(['schedule_run_id' => $scheduleRunId]);
            }
        }
    }
};