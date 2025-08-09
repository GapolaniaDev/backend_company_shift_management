<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $companySlug = $request->route('company') ?? 'default';
        
        $companyId = Company::where('slug', $companySlug)->value('id');
        
        if (!$companyId) {
            // If company not found, use default company
            $companyId = Company::where('slug', 'default')->value('id');
        }
        
        if ($companyId) {
            app()->instance('currentCompanyId', $companyId);
        }

        return $next($request);
    }
}
