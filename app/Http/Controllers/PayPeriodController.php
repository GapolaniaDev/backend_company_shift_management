<?php

namespace App\Http\Controllers;

use App\Models\PayPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PayPeriodController extends ApiController
{
    /**
     * Display a listing of pay periods.
     */
    public function index(Request $request)
    {
        [$page, $pageSize] = $this->getPageParams($request);
        
        $query = PayPeriod::query();
        
        // Date range filter
        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->input('start_date'));
        }
        
        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->input('end_date'));
        }
        
        // Fiscal week/year filter
        if ($request->has('fiscal_week')) {
            $query->where('fiscal_week', $request->input('fiscal_week'));
        }
        
        if ($request->has('fiscal_year')) {
            $query->where('fiscal_year', $request->input('fiscal_year'));
        }
        
        // Sort
        $sortBy = $request->input('sort_by', 'start_date');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSortFields = ['start_date', 'end_date', 'fiscal_week', 'fiscal_year', 'created_at'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }
        
        $payPeriods = $query->paginate($pageSize, ['*'], 'page', $page);
        
        return $this->paginatedResponse($payPeriods);
    }

    /**
     * Store a newly created pay period.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'fiscal_week' => 'required|integer|min:1|max:53',
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'is_closed' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }
        
        // Check for overlapping pay periods
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        
        $overlappingPeriod = PayPeriod::where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->first();
            
        if ($overlappingPeriod) {
            return $this->errorResponse('The new pay period overlaps with an existing pay period.', 422);
        }
        
        // Check for duplicate fiscal week/year
        $duplicateFiscal = PayPeriod::where('fiscal_year', $request->input('fiscal_year'))
            ->where('fiscal_week', $request->input('fiscal_week'))
            ->first();
            
        if ($duplicateFiscal) {
            return $this->errorResponse('A pay period with this fiscal week and year already exists.', 422);
        }

        try {
            $payPeriod = PayPeriod::create([
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'fiscal_week' => $request->input('fiscal_week'),
                'fiscal_year' => $request->input('fiscal_year'),
                'is_closed' => $request->input('is_closed', false),
                'notes' => $request->input('notes'),
            ]);
            
            return $this->successResponse($payPeriod, 'Pay period created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create pay period: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified pay period.
     */
    public function show(string $id)
    {
        $payPeriod = PayPeriod::findOrFail($id);
        
        // Optionally load related shift data if needed
        // $payPeriod->load('shifts');
        
        return $this->successResponse($payPeriod);
    }

    /**
     * Update the specified pay period.
     */
    public function update(Request $request, string $id)
    {
        $payPeriod = PayPeriod::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'fiscal_week' => 'sometimes|required|integer|min:1|max:53',
            'fiscal_year' => 'sometimes|required|integer|min:2000|max:2100',
            'is_closed' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }
        
        // Check for date overlaps if dates are being changed
        if ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->input('start_date', $payPeriod->start_date);
            $endDate = $request->input('end_date', $payPeriod->end_date);
            
            $overlappingPeriod = PayPeriod::where('id', '!=', $id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->first();
                
            if ($overlappingPeriod) {
                return $this->errorResponse('The updated pay period would overlap with an existing pay period.', 422);
            }
        }
        
        // Check for duplicate fiscal week/year if those are being changed
        if ($request->has('fiscal_year') || $request->has('fiscal_week')) {
            $fiscalYear = $request->input('fiscal_year', $payPeriod->fiscal_year);
            $fiscalWeek = $request->input('fiscal_week', $payPeriod->fiscal_week);
            
            $duplicateFiscal = PayPeriod::where('id', '!=', $id)
                ->where('fiscal_year', $fiscalYear)
                ->where('fiscal_week', $fiscalWeek)
                ->first();
                
            if ($duplicateFiscal) {
                return $this->errorResponse('A pay period with this fiscal week and year already exists.', 422);
            }
        }

        try {
            $payPeriod->update($request->all());
            
            return $this->successResponse($payPeriod, 'Pay period updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update pay period: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified pay period.
     */
    public function destroy(string $id)
    {
        $payPeriod = PayPeriod::findOrFail($id);
        
        // Check if any shifts are associated with this pay period
        // This would be done through a relationship if it exists
        // if ($payPeriod->shifts()->count() > 0) {
        //     return $this->errorResponse('Cannot delete this pay period as it has shifts associated with it.', 422);
        // }
        
        // Check if the pay period is already closed
        if ($payPeriod->is_closed) {
            return $this->errorResponse('Cannot delete a closed pay period.', 422);
        }
        
        try {
            $payPeriod->delete();
            return $this->successResponse(null, 'Pay period deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete pay period: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get the current pay period
     */
    public function current()
    {
        $today = Carbon::today();
        
        $currentPayPeriod = PayPeriod::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
            
        if (!$currentPayPeriod) {
            return $this->errorResponse('No pay period found for the current date.', 404);
        }
        
        return $this->successResponse($currentPayPeriod);
    }
    
    /**
     * Generate multiple pay periods for a year
     */
    public function generateForYear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2000|max:2100',
            'period_length_days' => 'required|integer|in:7,14,15,28,30,31',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }
        
        $year = $request->input('year');
        $periodLength = $request->input('period_length_days');
        
        // Check if periods already exist for this year
        $existingCount = PayPeriod::where('fiscal_year', $year)->count();
        if ($existingCount > 0) {
            return $this->errorResponse("Pay periods already exist for {$year}. Please delete them first or choose a different year.", 422);
        }
        
        try {
            $startDate = Carbon::create($year, 1, 1);
            $endOfYear = Carbon::create($year, 12, 31);
            $payPeriods = [];
            $fiscalWeek = 1;
            
            while ($startDate->lessThan($endOfYear)) {
                $endDate = (clone $startDate)->addDays($periodLength - 1);
                
                // If the end date exceeds year end, cap it at year end
                if ($endDate->year > $year) {
                    $endDate = $endOfYear;
                }
                
                $payPeriod = PayPeriod::create([
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'fiscal_week' => $fiscalWeek,
                    'fiscal_year' => $year,
                    'is_closed' => false,
                ]);
                
                $payPeriods[] = $payPeriod;
                
                // Move to next period
                $startDate = (clone $endDate)->addDay();
                $fiscalWeek++;
            }
            
            return $this->successResponse([
                'count' => count($payPeriods),
                'pay_periods' => $payPeriods,
            ], "Successfully generated {$fiscalWeek} pay periods for {$year}");
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate pay periods: ' . $e->getMessage(), 500);
        }
    }
}