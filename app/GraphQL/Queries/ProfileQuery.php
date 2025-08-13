<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ProfileQuery
{
    /**
     * Get the authenticated user's profile
     */
    public function profile($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return Auth::user();
    }

    /**
     * Get the authenticated user's profile with employee data
     */
    public function profileWithEmployee($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return Auth::user()->load('employee');
    }
}