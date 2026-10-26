<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Require2FA
{
    protected TwoFactorAuthService $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado',
            ], 401);
        }

        // Verificar si el usuario requiere 2FA por su rol
        if ($this->twoFactorService->isRequired($user)) {
            // Si no tiene 2FA habilitado, bloquear acceso
            if (!$user->google2fa_enabled) {
                return response()->json([
                    'message' => '2FA es obligatorio para su rol. Por favor configure 2FA antes de continuar.',
                    'requires_2fa_setup' => true,
                ], 403);
            }
        }

        return $next($request);
    }
}
