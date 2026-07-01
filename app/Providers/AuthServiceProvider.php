<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     * (Las policies RAO MOTOS se registran a medida que se construyen los CU.)
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    public function boot(): void
    {
        // RN1: el admin es superusuario, salta todas las policies.
        Gate::before(function ($user, $ability) {
            return $user->esAdmin() ? true : null;
        });
    }
}
