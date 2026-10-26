<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que aplica automáticamente filtros de alcance por campaña
 * basándose en las campañas asignadas al usuario autenticado.
 * 
 * Los usuarios solo pueden acceder a datos de las campañas a las que están asignados.
 * Superadministradores tienen acceso completo.
 */
class CampaignScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado, continuar sin filtro
        if (!$user) {
            return $next($request);
        }

        // Superadministradores tienen acceso completo
        if ($user->hasRole('Superadministrador')) {
            return $next($request);
        }

        // Obtener IDs de campañas asignadas al usuario
        $campanasAccesibles = $user->campanas()->pluck('campanas.id')->toArray();

        // Agregar los IDs de campañas al request para uso posterior
        $request->merge([
            '_accessible_campaign_ids' => $campanasAccesibles
        ]);

        // También lo guardamos en el usuario para uso en scopes
        $user->setAttribute('accessible_campaign_ids', $campanasAccesibles);

        return $next($request);
    }
}
