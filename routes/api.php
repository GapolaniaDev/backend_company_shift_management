<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayPeriodController;
use App\Http\Controllers\ShiftConfigurationController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftGenerationController;
use App\Http\Controllers\ShiftTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authentication routes
Route::middleware('auth:api')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
});

// Employee routes - Role restricted
Route::middleware(['auth:api'])->prefix('employees')->group(function () {
    // Employee can only access their own data
    Route::get('/me', [EmployeeController::class, 'me']);

    // Supervisor can access their employees
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/supervisees', [EmployeeController::class, 'supervisees']);
    });

    // Admin can do everything
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
        Route::post('/{id}/assign-supervisor', [EmployeeController::class, 'assignSupervisor']);
    });
});

// Shift routes with role-based access
Route::middleware(['auth:api'])->prefix('shifts')->group(function () {
    // Routes available to all authenticated users
    Route::get('/today', [ShiftController::class, 'getTodayShift']);
    Route::put('/{id}/update-clock', [ShiftController::class, 'updateClock']);

    // Employee routes - restricted to their own shifts
    Route::get('/my-shifts', [ShiftController::class, 'myShifts']);

    // Supervisor routes
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::get('/', [ShiftController::class, 'index']);
        Route::get('/team', [ShiftController::class, 'teamShifts']);
    });

    // Admin routes
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/', [ShiftController::class, 'store']);
        Route::get('/{id}', [ShiftController::class, 'show']);
        Route::put('/{id}', [ShiftController::class, 'update']);
        Route::delete('/{id}', [ShiftController::class, 'destroy']);
    });
});

// ShiftType routes - mostly admin only
Route::middleware(['auth:api', 'role:admin'])->prefix('shift-types')->group(function () {
    Route::get('/', [ShiftTypeController::class, 'index']);
    Route::post('/', [ShiftTypeController::class, 'store']);
    Route::get('/{id}', [ShiftTypeController::class, 'show']);
    Route::put('/{id}', [ShiftTypeController::class, 'update']);
    Route::delete('/{id}', [ShiftTypeController::class, 'destroy']);
});

// ShiftConfiguration routes with role-based access
Route::middleware(['auth:api'])->prefix('shift-configurations')->group(function () {
    // Admin can do everything
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/', [ShiftConfigurationController::class, 'index']);
        Route::post('/', [ShiftConfigurationController::class, 'store']);
        Route::get('/{id}', [ShiftConfigurationController::class, 'show']);
        Route::put('/{id}', [ShiftConfigurationController::class, 'update']);
        Route::delete('/{id}', [ShiftConfigurationController::class, 'destroy']);
    });

    // Supervisors can view and modify their team's configurations
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::get('/team', [ShiftConfigurationController::class, 'teamConfigurations']);
    });

    // Employees can only see their own
    Route::get('/my-configurations', [ShiftConfigurationController::class, 'myConfigurations']);
});

// PayPeriod routes - Admin only
Route::middleware(['auth:api', 'role:admin'])->prefix('pay-periods')->group(function () {
    Route::get('/', [PayPeriodController::class, 'index']);
    Route::post('/', [PayPeriodController::class, 'store']);
    Route::get('/{id}', [PayPeriodController::class, 'show']);
    Route::put('/{id}', [PayPeriodController::class, 'update']);
    Route::delete('/{id}', [PayPeriodController::class, 'destroy']);
});

// Dashboard routes with role-based restrictions
Route::middleware(['auth:api'])->group(function () {
    Route::get('/dashboard/employee', [DashboardController::class, 'employeeDashboard']);

    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::get('/dashboard/supervisor', [DashboardController::class, 'supervisorDashboard']);
    });

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard']);
    });
});

// Shift generation - admin and supervisor
Route::middleware(['auth:api', 'role:admin,supervisor'])->group(function () {
    Route::get('/generate-next-fortnight-shifts', [ShiftGenerationController::class, 'generateNextFortnightShifts']);
});
