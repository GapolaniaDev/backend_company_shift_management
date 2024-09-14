<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ShiftGenerationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->controller(DashboardController::class)->group(function () {
    Route::get('/employees', 'index');
});

Route::middleware('auth:api')->controller(ShiftGenerationController::class)->group(function () {
    Route::get('/generateNextFortnightShifts', 'generateNextFortnightShifts');
});

Route::middleware('auth:api')->get('/data', function () {
    return [
        [
            "nombre" => "Juan Pérez",
            "edad" => 30,
            "email" => "juan.perez@example.com",
            "telefono" => "123-456-7890",
            "direccion" => "Calle Falsa 123, Ciudad Ejemplo"
        ],
        [
            "nombre" => "María García",
            "edad" => 25,
            "email" => "maria.garcia@example.com",
            "telefono" => "321-654-0987",
            "direccion" => "Avenida Siempre Viva 742, Ciudad Demo"
        ],
        [
            "nombre" => "Carlos Hernández",
            "edad" => 35,
            "email" => "carlos.hernandez@example.com",
            "telefono" => "987-654-3210",
            "direccion" => "Boulevard del Sol 456, Municipio Prueba"
        ],
        [
            "nombre" => "Ana López",
            "edad" => 28,
            "email" => "ana.lopez@example.com",
            "telefono" => "456-789-1230",
            "direccion" => "Calle de la Luna 789, Villa Test"
        ],
        [
            "nombre" => "Pedro Martínez",
            "edad" => 40,
            "email" => "pedro.martinez@example.com",
            "telefono" => "789-012-3456",
            "direccion" => "Calle del Arco 321, Pueblo Ejemplo"
        ],
        [
            'Monday' => [
                'date_start' => date('Y-m-d 00:00:00', strtotime('Monday')),
                'date_finish' => date('Y-m-d 23:59:59', strtotime('Monday')),
            ],
            'Tuesday' => [
                'date_start' => date('Y-m-d 00:00:00', strtotime('Tuesday')),
                'date_finish' => date('Y-m-d 23:59:59', strtotime('Tuesday')),
            ],
            'Wednesday' => [
                'date_start' => date('Y-m-d 00:00:00', strtotime('Wednesday')),
                'date_finish' => date('Y-m-d 23:59:59', strtotime('Wednesday')),
            ],
            'Thursday' => [
                'date_start' => date('Y-m-d 00:00:00', strtotime('Thursday')),
                'date_finish' => date('Y-m-d 23:59:59', strtotime('Thursday')),
            ],
            'Friday' => [
                'date_start' => date('Y-m-d 00:00:00', strtotime('Friday')),
                'date_finish' => date('Y-m-d 23:59:59', strtotime('Friday')),
            ],
            'Saturday' => [
                'date_start' => date('Y-m-d 00:00:00', strtotime('Saturday')),
                'date_finish' => date('Y-m-d 23:59:59', strtotime('Saturday')),
            ],
            'Sunday' => [
                'date_start' => date('Y-m-d 00:00:00', strtotime('Sunday')),
                'date_finish' => date('Y-m-d 23:59:59', strtotime('Sunday')),
            ],
        ]
    ];
});

Route::middleware('auth:api')->get('/user', [AuthController::class, 'me']);
Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
