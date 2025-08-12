<?php

namespace App\GraphQL\Mutations;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EmployeeMutator
{
    public function create($root, array $args)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            throw new \Nuwave\Lighthouse\Exceptions\AuthenticationException('Authentication required');
        }

        if (! $user->company_id) {
            throw new \Nuwave\Lighthouse\Exceptions\AuthorizationException('User must belong to a company');
        }

        // Validate that the user belongs to the same company (basic validation)
        $this->validateEmployeeData($args, $user->company_id);

        // Force company_id based on authenticated user's company
        $args['company_id'] = $user->company_id;

        return Employee::create($args);
    }

    public function update($root, array $args)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            throw new \Nuwave\Lighthouse\Exceptions\AuthenticationException('Authentication required');
        }

        if (! $user->company_id) {
            throw new \Nuwave\Lighthouse\Exceptions\AuthorizationException('User must belong to a company');
        }

        $employeeId = $args['id'];
        unset($args['id']);

        // Load employee and verify it belongs to user's company
        $employee = Employee::where('id', $employeeId)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        // Validate updated data
        $this->validateEmployeeData($args, $user->company_id, $employee);

        $employee->update($args);

        return $employee->fresh();
    }

    public function delete($root, array $args)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            throw new \Nuwave\Lighthouse\Exceptions\AuthenticationException('Authentication required');
        }

        if (! $user->company_id) {
            throw new \Nuwave\Lighthouse\Exceptions\AuthorizationException('User must belong to a company');
        }

        $employee = Employee::where('id', $args['id'])
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        return $employee->delete();
    }

    private function validateEmployeeData(array $args, $companyId, $existingEmployee = null)
    {
        // Validate supervisor exists and belongs to same company
        if (isset($args['supervisor_id'])) {
            $supervisor = Employee::where('id', $args['supervisor_id'])
                ->where('company_id', $companyId)
                ->first();

            if (! $supervisor) {
                throw ValidationException::withMessages([
                    'supervisor_id' => ['The selected supervisor does not exist or does not belong to your company.'],
                ]);
            }

            // Prevent self-supervision
            if ($existingEmployee && $args['supervisor_id'] == $existingEmployee->id) {
                throw ValidationException::withMessages([
                    'supervisor_id' => ['An employee cannot be their own supervisor.'],
                ]);
            }
        }

        // Validate user_id exists (basic validation)
        if (isset($args['user_id'])) {
            $user = \App\Models\User::find($args['user_id']);
            if (! $user) {
                throw ValidationException::withMessages([
                    'user_id' => ['The selected user does not exist.'],
                ]);
            }
        }
    }
}
