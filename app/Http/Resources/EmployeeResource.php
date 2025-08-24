<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();
        $isSupervisorOfThis = $user->isSupervisor() && $user->employee && $this->supervisor_id === $user->employee->id;

        // Current and next week date ranges
        $currentWeekStart = Carbon::now()->startOfWeek();
        $currentWeekEnd = Carbon::now()->endOfWeek();
        $nextWeekStart = Carbon::now()->addWeek()->startOfWeek();
        $nextWeekEnd = Carbon::now()->addWeek()->endOfWeek();

        return [
            'id' => $this->id,
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ],
            'supervisor' => $this->when($this->supervisor, [
                'id' => $this->supervisor?->id,
                'name' => $this->supervisor?->full_name,
            ]),
            'user' => $this->when($this->user, [
                'id' => $this->user?->id,
                'email' => $this->user?->email,
                'role' => $this->user?->role,
                'email_verified_at' => $this->user?->email_verified_at?->toISOString(),
            ]),
            'summary' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone_number' => $this->phone_number,
                'weekly_working_hours' => $this->weekly_working_hours,
            ],
            'personal' => $this->when(
                $isAdmin || $isSupervisorOfThis,
                [
                    'address' => $this->address,
                    'tax_number' => $this->tax_number,
                    'abn' => $this->abn,
                    'bsb' => $this->bsb,
                    'account' => $this->account,
                ]
            ),
            'upcoming_shifts' => $this->when(
                $this->shifts,
                $this->shifts
                    ->whereBetween('date_start', [$currentWeekStart, $nextWeekEnd])
                    ->map(function ($shift) {
                        return [
                            'id' => $shift->id,
                            'date_start' => $shift->date_start->toISOString(),
                            'date_end' => $shift->date_end->toISOString(),
                            'total_hours' => $shift->total_hours,
                            'status' => $this->getShiftStatus($shift),
                            'shift_type' => [
                                'id' => $shift->shiftType->id,
                                'name' => $shift->shiftType->name,
                            ],
                            'location' => $this->when($shift->location, [
                                'id' => $shift->location?->id,
                                'name' => $shift->location?->name,
                                'address' => $shift->location?->address,
                            ]),
                        ];
                    })
                    ->values()
            ),
            'assignments' => $this->when(
                $this->shiftAssignments,
                $this->shiftAssignments
                    ->filter(function ($assignment) use ($currentWeekStart, $nextWeekEnd) {
                        return $assignment->shift && 
                               $assignment->shift->date_start >= $currentWeekStart && 
                               $assignment->shift->date_start <= $nextWeekEnd;
                    })
                    ->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'shift_id' => $assignment->shift_id,
                            'status' => $assignment->status,
                            'assignment_type' => $assignment->assignment_type,
                            'assigned_at' => $assignment->assigned_at?->toISOString(),
                        ];
                    })
                    ->values()
            ),
            'replacement_requests' => $this->when(
                $this->replacementRequests,
                $this->replacementRequests
                    ->where('status', '!=', 'fulfilled')
                    ->map(function ($request) {
                        return [
                            'id' => $request->id,
                            'shift_assignment_id' => $request->shift_assignment_id,
                            'status' => $request->status,
                            'reason' => $request->reason,
                            'urgency' => $request->urgency,
                            'needed_by' => $request->needed_by?->toISOString(),
                        ];
                    })
                    ->values()
            ),
            'replacement_bids' => $this->when(
                $this->replacementBids,
                $this->replacementBids
                    ->where('bid_status', '!=', 'rejected')
                    ->map(function ($bid) {
                        return [
                            'id' => $bid->id,
                            'replacement_request_id' => $bid->replacement_request_id,
                            'status' => $bid->bid_status,
                            'bid_amount' => $bid->bid_amount,
                            'message' => $bid->message,
                            'bid_at' => $bid->bid_at?->toISOString(),
                        ];
                    })
                    ->values()
            ),
            'clock_activity' => $this->getClockActivity(),
            'audit' => $this->when(
                $isAdmin,
                $this->getAuditData($request)
            ),
        ];
    }

    /**
     * Get shift status based on state and clock times
     */
    private function getShiftStatus($shift): string
    {
        switch ($shift->state) {
            case 0: return 'not_started';
            case 1: return 'in_progress';
            case 2: return 'completed';
            default: return 'unknown';
        }
    }

    /**
     * Get clock activity data
     */
    private function getClockActivity(): array
    {
        $lastClockOnShift = $this->shifts()
            ->whereNotNull('clock_on_time')
            ->orderBy('clock_on_time', 'desc')
            ->first();

        $lastClockOffShift = $this->shifts()
            ->whereNotNull('clock_off_time')
            ->orderBy('clock_off_time', 'desc')
            ->first();

        return [
            'last_clock_on' => $lastClockOnShift ? [
                'time' => $lastClockOnShift->clock_on_time->toISOString(),
                'lat' => $lastClockOnShift->clock_on_lat,
                'lng' => $lastClockOnShift->clock_on_lng,
                'timezone' => $lastClockOnShift->timezone_start,
            ] : null,
            'last_clock_off' => $lastClockOffShift ? [
                'time' => $lastClockOffShift->clock_off_time->toISOString(),
                'lat' => $lastClockOffShift->clock_off_lat,
                'lng' => $lastClockOffShift->clock_off_lng,
                'timezone' => $lastClockOffShift->timezone_end,
            ] : null,
        ];
    }

    /**
     * Get audit data (admin only)
     */
    private function getAuditData($request): ?array
    {
        if (!$this->user) {
            return null;
        }

        // Get last session data from sessions table
        $lastSession = \DB::table('sessions')
            ->where('user_id', $this->user->id)
            ->orderBy('last_activity', 'desc')
            ->first();

        return [
            'last_session' => $lastSession ? [
                'ip_address' => $lastSession->ip_address,
                'user_agent' => $lastSession->user_agent,
                'last_activity' => Carbon::createFromTimestamp($lastSession->last_activity)->toISOString(),
            ] : null,
        ];
    }
}