<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Simpatizante::class => \App\Policies\SimpatizantePolicy::class,
        \App\Models\Territorio::class => \App\Policies\TerritorioPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Campana::class => \App\Policies\CampanaPolicy::class,
        \App\Models\Auditoria::class => \App\Policies\AuditoriaPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Definir gates adicionales si es necesario
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Superadministrador') ? true : null;
        });
    }
}
