<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiController extends Controller
{
    /**
     * Default page size for pagination
     */
    protected int $defaultPageSize = 15;
    
    /**
     * Maximum page size for pagination
     */
    protected int $maxPageSize = 100;
    
    /**
     * Success response
     */
    protected function successResponse($data, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $code);
    }
    
    /**
     * Error response
     */
    protected function errorResponse(string $message, int $code, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $code);
    }
    
    /**
     * Handle pagination parameters
     */
    protected function getPageParams(Request $request): array
    {
        $page = $request->input('page', 1);
        $pageSize = min(
            $request->input('per_page', $this->defaultPageSize), 
            $this->maxPageSize
        );
        
        return [$page, $pageSize];
    }
    
    /**
     * Format paginated response
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, ?string $message = null): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'links' => [
                    'next' => $paginator->nextPageUrl(),
                    'prev' => $paginator->previousPageUrl(),
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                ],
            ],
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        return response()->json($response);
    }
}