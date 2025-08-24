<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ProfileMutator
{
    /**
     * Update the authenticated user's profile
     */
    public function update($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('Unauthenticated');
        }

        // Validate unique email if being updated
        if (isset($args['email']) && $args['email'] !== $user->email) {
            $existingUser = User::where('email', $args['email'])
                              ->where('id', '!=', $user->id)
                              ->first();
            
            if ($existingUser) {
                throw ValidationException::withMessages([
                    'email' => ['The email has already been taken.']
                ]);
            }
        }

        $user->update(array_filter($args));
        
        return $user->fresh();
    }

    /**
     * Change the authenticated user's password
     */
    public function changePassword($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('Unauthenticated');
        }

        // Verify current password
        if (!Hash::check($args['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.']
            ]);
        }

        // Verify password confirmation
        if ($args['new_password'] !== $args['new_password_confirmation']) {
            throw ValidationException::withMessages([
                'new_password_confirmation' => ['The new password confirmation does not match.']
            ]);
        }

        // Validate new password strength (minimum 8 characters)
        if (strlen($args['new_password']) < 8) {
            throw ValidationException::withMessages([
                'new_password' => ['The new password must be at least 8 characters.']
            ]);
        }

        $user->update([
            'password' => Hash::make($args['new_password'])
        ]);

        return true;
    }

    /**
     * Delete the authenticated user's profile
     */
    public function delete($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('Unauthenticated');
        }

        // Prevent admin users from deleting themselves if they're the only admin
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            
            if ($adminCount <= 1) {
                throw new \Exception('Cannot delete the last admin user');
            }
        }

        // Check if user has associated employee data
        if ($user->employee) {
            throw new \Exception('Cannot delete profile with associated employee data. Please contact an administrator.');
        }

        $user->delete();
        
        return true;
    }

    /**
     * Create a new user profile (typically for self-registration if enabled)
     */
    public function create($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        // Check if email already exists
        $existingUser = User::where('email', $args['email'])->first();
        
        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.']
            ]);
        }

        // Validate password strength
        if (strlen($args['password']) < 8) {
            throw ValidationException::withMessages([
                'password' => ['The password must be at least 8 characters.']
            ]);
        }

        // Create user with company_id from current company context
        $user = User::create([
            'name' => $args['name'],
            'email' => $args['email'],
            'password' => Hash::make($args['password']),
            'role' => $args['role'] ?? 'employee',
            'company_id' => Auth::user() ? Auth::user()->company_id : 1, // Default to company 1 if no auth context
        ]);

        return $user;
    }
}