<?php

namespace App\Policies;

use App\Models\Simpatizante;
use App\Models\User;

class SimpatizantePolicy
{
    /**
     * Determinar si el usuario puede ver cualquier simpatizante.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('simpatizante.ver');
    }

    /**
     * Determinar si el usuario puede ver el simpatizante.
     */
    public function view(User $user, Simpatizante $simpatizante): bool
    {
        if (!$user->can('simpatizante.ver')) {
            return false;
        }

        // Verificar alcance por campaña
        if (!$this->inUserCampana($user, $simpatizante)) {
            return false;
        }

        // Verificar alcance por territorio
        return $this->inUserTerritorio($user, $simpatizante);
    }

    /**
     * Determinar si el usuario puede crear simpatizantes.
     */
    public function create(User $user): bool
    {
        return $user->can('simpatizante.crear');
    }

    /**
     * Determinar si el usuario puede actualizar el simpatizante.
     */
    public function update(User $user, Simpatizante $simpatizante): bool
    {
        if (!$user->can('simpatizante.editar')) {
            return false;
        }

        // Verificar alcance por campaña
        if (!$this->inUserCampana($user, $simpatizante)) {
            return false;
        }

        // Verificar alcance por territorio
        return $this->inUserTerritorio($user, $simpatizante);
    }

    /**
     * Determinar si el usuario puede eliminar el simpatizante.
     */
    public function delete(User $user, Simpatizante $simpatizante): bool
    {
        if (!$user->can('simpatizante.eliminar')) {
            return false;
        }

        // Solo Superadmin puede eliminar
        return $user->hasRole('Superadministrador');
    }

    /**
     * Determinar si el usuario puede exportar simpatizantes.
     */
    public function export(User $user): bool
    {
        return $user->can('simpatizante.exportar');
    }

    /**
     * Verificar si el simpatizante está en una campaña del usuario.
     */
    private function inUserCampana(User $user, Simpatizante $simpatizante): bool
    {
        return $user->hasAccessToCampana((string) $simpatizante->campana_id);
    }

    /**
     * Verificar si el simpatizante está en territorio del usuario.
     */
    private function inUserTerritorio(User $user, Simpatizante $simpatizante): bool
    {
        return $user->hasAccessToTerritorio((string) $simpatizante->territorio_id);
    }
}
