<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->controller(DashboardController::class)->group(function () {
    Route::get('/employees', 'index');
    // Otras rutas del controlador pueden ser añadidas aquí
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
        ]
    ];
});

Route::middleware('auth:api')->get('/user', [AuthController::class, 'me']);
Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
