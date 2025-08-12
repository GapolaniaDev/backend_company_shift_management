<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\Models\Shift::class => \App\Policies\ShiftPolicy::class,
        \App\Models\Employee::class => \App\Policies\EmployeePolicy::class,
        \App\Models\ShiftType::class => \App\Policies\ShiftTypePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
