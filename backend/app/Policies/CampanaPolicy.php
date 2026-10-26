<?php

namespace App\Policies;

use App\Models\Campana;
use App\Models\User;

class CampanaPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier campaña.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('campana.ver');
    }

    /**
     * Determinar si el usuario puede ver la campaña.
     */
    public function view(User $user, Campana $campana): bool
    {
        if (!$user->can('campana.ver')) {
            return false;
        }

        // Superadmin puede ver todas
        if ($user->hasRole('Superadministrador')) {
            return true;
        }

        // Verificar que el usuario pertenezca a la campaña
        return $user->campanas->contains($campana->id);
    }

    /**
     * Determinar si el usuario puede crear campañas.
     */
    public function create(User $user): bool
    {
        return $user->can('campana.administrar');
    }

    /**
     * Determinar si el usuario puede actualizar la campaña.
     */
    public function update(User $user, Campana $campana): bool
    {
        return $user->can('campana.administrar');
    }

    /**
     * Determinar si el usuario puede eliminar la campaña.
     */
    public function delete(User $user, Campana $campana): bool
    {
        return $user->can('campana.administrar');
    }
}
