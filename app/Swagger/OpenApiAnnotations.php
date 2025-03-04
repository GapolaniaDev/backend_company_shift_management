<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *     title="Shift Management API",
 *     version="1.0.0",
 *     description="API para sistema de gestión de turnos y empleados",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="API Support"
 *     )
 * )
 * 
 * @OA\Server(
 *     description="API Server",
 *     url="/"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     securityScheme="bearerAuth",
 *     bearerFormat="JWT"
 * )
 */
class OpenApiAnnotations
{
    // This class only exists for Swagger annotations
}