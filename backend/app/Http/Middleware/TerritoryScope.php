<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que aplica automáticamente filtros de alcance territorial
 * basándose en los territorios asignados al usuario autenticado.
 * 
 * Los usuarios tienen acceso a:
 * - Sus territorios directamente asignados
 * - Todos los territorios descendientes (usando función PostgreSQL)
 * - Superadministradores tienen acceso completo
 */
class TerritoryScope
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

        // Obtener todos los territorios accesibles (asignados + descendientes)
        $territoriosAccesibles = $this->getAccessibleTerritories($user);

        // Agregar los IDs de territorios al request para uso posterior
        $request->merge([
            '_accessible_territory_ids' => $territoriosAccesibles
        ]);

        // También lo guardamos en el usuario para uso en scopes
        $user->setAttribute('accessible_territory_ids', $territoriosAccesibles);

        return $next($request);
    }

    /**
     * Obtener todos los territorios a los que el usuario tiene acceso.
     * Incluye territorios asignados y todos sus descendientes.
     *
     * @param \App\Models\User $user
     * @return array Array de IDs de territorios
     */
    private function getAccessibleTerritories($user): array
    {
        // Delegar al modelo User que ya tiene la lógica MySQL correcta
        return $user->getAccessibleTerritoryIds();
    }
}
