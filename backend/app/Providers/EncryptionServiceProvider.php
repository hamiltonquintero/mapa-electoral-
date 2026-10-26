<?php

namespace App\Providers;

use App\Services\EncryptionService;
use Illuminate\Support\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EncryptionService::class, function ($app) {
            return new EncryptionService();
        });

        // Alias para facilitar el uso
        $this->app->alias(EncryptionService::class, 'encryption');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Validar configuración en producción
        if ($this->app->environment('production')) {
            $encryption = $this->app->make(EncryptionService::class);
            $errors = $encryption->validateConfiguration();

            if (!empty($errors)) {
                throw new \RuntimeException(
                    'Configuración de cifrado inválida: ' . implode(', ', $errors)
                );
            }
        }
    }
}
