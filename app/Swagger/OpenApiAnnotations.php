<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *     title="Shift Management API",
 *     version="1.0.0",
 *     description="API for shift and employee management system",
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