<?php

namespace App\Http\Controllers;

use App\Models\ShiftType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ShiftTypeController extends ApiController
{
    /**
     * Display a listing of shift types.
     */
    public function index(Request $request)
    {
        [$page, $pageSize] = $this->getPageParams($request);
        
        $query = ShiftType::query();
        
        // Search by name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSortFields = ['name', 'weekly_hours', 'created_at'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }
        
        $shiftTypes = $query->paginate($pageSize, ['*'], 'page', $page);
        
        return $this->paginatedResponse($shiftTypes);
    }

    /**
     * Store a newly created shift type.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('shift_types')->where(fn($q) => $q->where('company_id', app('currentCompanyId', 1)))
            ],
            'weekly_hours' => 'required|numeric|min:0|max:168',
            'schedule' => 'required|json',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $shiftType = ShiftType::create([
                'name' => $request->name,
                'weekly_hours' => $request->weekly_hours,
                'schedule' => $request->schedule,
            ]);
            
            return $this->successResponse($shiftType, 'Shift type created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create shift type: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified shift type.
     */
    public function show(string $id)
    {
        $shiftType = ShiftType::with('shiftConfigurations')->findOrFail($id);
        
        return $this->successResponse($shiftType);
    }

    /**
     * Update the specified shift type.
     */
    public function update(Request $request, string $id)
    {
        $shiftType = ShiftType::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('shift_types')->where(fn($q) => $q->where('company_id', app('currentCompanyId', 1)))->ignore($id)
            ],
            'weekly_hours' => 'sometimes|required|numeric|min:0|max:168',
            'schedule' => 'sometimes|required|json',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $shiftType->update($request->all());
            
            return $this->successResponse($shiftType, 'Shift type updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update shift type: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified shift type.
     */
    public function destroy(string $id)
    {
        $shiftType = ShiftType::findOrFail($id);
        
        // Check if any shifts are using this shift type
        if ($shiftType->shifts()->count() > 0) {
            return $this->errorResponse('Cannot delete this shift type as it is being used by shifts.', 422);
        }
        
        // Check if any configurations are using this shift type
        if ($shiftType->shiftConfigurations()->count() > 0) {
            return $this->errorResponse('Cannot delete this shift type as it is being used in shift configurations.', 422);
        }
        
        try {
            $shiftType->delete();
            return $this->successResponse(null, 'Shift type deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete shift type: ' . $e->getMessage(), 500);
        }
    }
}