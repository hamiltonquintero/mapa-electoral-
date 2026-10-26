<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Check2FA
{
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

        // Si el usuario tiene 2FA habilitado
        if ($user->google2fa_enabled) {
            // Verificar si ya pasó la verificación 2FA en esta sesión
            if (!session('2fa_verified')) {
                return response()->json([
                    'message' => 'Se requiere verificación 2FA',
                    'requires_2fa' => true,
                ], 403);
            }
        }

        return $next($request);
    }
}
