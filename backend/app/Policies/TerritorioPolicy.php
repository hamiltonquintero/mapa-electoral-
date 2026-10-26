<?php

namespace App\Policies;

use App\Models\Territorio;
use App\Models\User;

class TerritorioPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier territorio.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('territorio.ver');
    }

    /**
     * Determinar si el usuario puede ver el territorio.
     */
    public function view(User $user, Territorio $territorio): bool
    {
        if (!$user->can('territorio.ver')) {
            return false;
        }

        // Superadmin puede ver todos
        if ($user->hasRole('Superadministrador')) {
            return true;
        }

        // Verificar si el usuario tiene acceso a este territorio o sus ancestros
        return $this->hasAccessToTerritorio($user, $territorio);
    }

    /**
     * Determinar si el usuario puede crear territorios.
     */
    public function create(User $user): bool
    {
        return $user->can('territorio.administrar');
    }

    /**
     * Determinar si el usuario puede actualizar el territorio.
     */
    public function update(User $user, Territorio $territorio): bool
    {
        return $user->can('territorio.administrar');
    }

    /**
     * Determinar si el usuario puede eliminar el territorio.
     */
    public function delete(User $user, Territorio $territorio): bool
    {
        return $user->can('territorio.administrar');
    }

    /**
     * Verificar si el usuario tiene acceso al territorio.
     */
    private function hasAccessToTerritorio(User $user, Territorio $territorio): bool
    {
        // Si no tiene territorios asignados, no tiene acceso
        if ($user->territorios->isEmpty()) {
            return false;
        }

        // Obtener IDs de territorios del usuario incluyendo descendientes
        $territorioIds = $user->territorios->flatMap(function ($t) {
            $descendientes = $t->obtenerDescendientes();
            return array_column($descendientes, 'id');
        })->unique()->toArray();

        // Verificar si el territorio está en la lista
        return in_array($territorio->id, $territorioIds);
    }
}
