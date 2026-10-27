<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Garantizar que el binding 'files' (Filesystem) existe.
        // Sanctum y SessionManager lo necesitan antes de que
        // FilesystemServiceProvider esté completamente cargado.
        $this->app->bindIf('files', fn() => new \Illuminate\Filesystem\Filesystem());
    }

    public function boot(): void
    {
        // ── PostGIS ya está habilitado en las migraciones ─────

        // ── Claves de cifrado (solo producción) ──────────────
        if (app()->environment('production')) {
            if (empty(config('encryption.field_key'))) {
                throw new \RuntimeException('FIELD_ENCRYPTION_KEY no está configurada');
            }
            if (empty(config('encryption.cedula_hash_key'))) {
                throw new \RuntimeException('CEDULA_HASH_KEY no está configurada');
            }
        }

        // ── Rate limiting ─────────────────────────────────────
        // Registrar DESPUÉS de que todos los providers estén booted
        // para evitar dependencia circular con CacheServiceProvider.
        $this->callAfterResolving('router', function () {
            RateLimiter::for('login', function (Request $request) {
                return [
                    Limit::perMinute(5)->by($request->ip()),
                    Limit::perMinute(3)->by($request->input('email', '') . '|' . $request->ip()),
                ];
            });

            RateLimiter::for('api', function (Request $request) {
                return $request->user()
                    ? Limit::perMinute(120)->by($request->user()->id)
                    : Limit::perMinute(30)->by($request->ip());
            });

            RateLimiter::for('exportaciones', function (Request $request) {
                return Limit::perHour(5)->by(
                    ($request->user()?->id ?? $request->ip()) . '|exportacion'
                );
            });
        });
    }
}
