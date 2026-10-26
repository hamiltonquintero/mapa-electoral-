<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para aplicar automáticamente filtros de alcance de usuario
 * (territorial y por campaña) en consultas de modelos.
 */
trait AppliesUserScope
{
    /**
     * Aplicar filtro de territorios accesibles a una consulta.
     *
     * @param Builder $query
     * @param User $user
     * @param string $territorioColumn Nombre de la columna de territorio (por defecto: territorio_id)
     * @return Builder
     */
    protected function applyTerritoryScope(Builder $query, User $user, string $territorioColumn = 'territorio_id'): Builder
    {
        // Superadministradores ven todo
        if ($user->hasRole('Superadministrador')) {
            return $query;
        }

        $territoriosAccesibles = $user->getAccessibleTerritoryIds();

        if (empty($territoriosAccesibles)) {
            // Usuario sin territorios asignados no ve nada
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($territorioColumn, $territoriosAccesibles);
    }

    /**
     * Aplicar filtro de campañas accesibles a una consulta.
     *
     * @param Builder $query
     * @param User $user
     * @param string $campanaColumn Nombre de la columna de campaña (por defecto: campana_id)
     * @return Builder
     */
    protected function applyCampaignScope(Builder $query, User $user, string $campanaColumn = 'campana_id'): Builder
    {
        // Superadministradores ven todo
        if ($user->hasRole('Superadministrador')) {
            return $query;
        }

        $campanasAccesibles = $user->getAccessibleCampaignIds();

        if (empty($campanasAccesibles)) {
            // Usuario sin campañas asignadas no ve nada
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($campanaColumn, $campanasAccesibles);
    }

    /**
     * Aplicar ambos filtros (territorio y campaña) a una consulta.
     *
     * @param Builder $query
     * @param User $user
     * @param string $territorioColumn
     * @param string $campanaColumn
     * @return Builder
     */
    protected function applyUserScope(
        Builder $query,
        User $user,
        string $territorioColumn = 'territorio_id',
        string $campanaColumn = 'campana_id'
    ): Builder {
        return $this->applyTerritoryScope(
            $this->applyCampaignScope($query, $user, $campanaColumn),
            $user,
            $territorioColumn
        );
    }

    /**
     * Verificar si el usuario tiene acceso a un territorio específico.
     *
     * @param User $user
     * @param string $territorioId
     * @return bool
     */
    protected function userHasAccessToTerritory(User $user, string $territorioId): bool
    {
        return $user->hasAccessToTerritorio($territorioId);
    }

    /**
     * Verificar si el usuario tiene acceso a una campaña específica.
     *
     * @param User $user
     * @param string $campanaId
     * @return bool
     */
    protected function userHasAccessToCampaign(User $user, string $campanaId): bool
    {
        return $user->hasAccessToCampana($campanaId);
    }

    /**
     * Obtener territorios accesibles del usuario.
     *
     * @param User $user
     * @return array
     */
    protected function getAccessibleTerritories(User $user): array
    {
        return $user->getAccessibleTerritoryIds();
    }

    /**
     * Obtener campañas accesibles del usuario.
     *
     * @param User $user
     * @return array
     */
    protected function getAccessibleCampaigns(User $user): array
    {
        return $user->getAccessibleCampaignIds();
    }
}
