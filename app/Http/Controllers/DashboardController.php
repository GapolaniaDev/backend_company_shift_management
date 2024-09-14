<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtener todos los empleados
        $employees = Employee::all();

        // Formatear la respuesta
        $formattedEmployees = $employees->map(function($employee) {
            return [
                'firstName' => $employee->first_name,
                'lastName' => $employee->last_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'address' => $employee->address,
            ];
        });

        // Retornar los empleados en formato JSON
        return response()->json($formattedEmployees);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
