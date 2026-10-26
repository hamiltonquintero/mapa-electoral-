<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier usuario.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('usuario.ver');
    }

    /**
     * Determinar si el usuario puede ver otro usuario.
     */
    public function view(User $user, User $targetUser): bool
    {
        if (!$user->can('usuario.ver')) {
            return false;
        }

        // Superadmin puede ver todos
        if ($user->hasRole('Superadministrador')) {
            return true;
        }

        // Admin de campaña puede ver usuarios de sus campañas
        if ($user->hasRole('Administrador de Campaña')) {
            return $user->campanas->intersect($targetUser->campanas)->isNotEmpty();
        }

        // Puede verse a sí mismo
        return $user->id === $targetUser->id;
    }

    /**
     * Determinar si el usuario puede crear usuarios.
     */
    public function create(User $user): bool
    {
        return $user->can('usuario.crear');
    }

    /**
     * Determinar si el usuario puede actualizar otro usuario.
     */
    public function update(User $user, User $targetUser): bool
    {
        if (!$user->can('usuario.editar')) {
            return false;
        }

        // Superadmin puede editar todos
        if ($user->hasRole('Superadministrador')) {
            return true;
        }

        // Admin de campaña puede editar usuarios de sus campañas
        if ($user->hasRole('Administrador de Campaña')) {
            // No puede editar otros admins o superadmins
            if ($targetUser->hasAnyRole(['Superadministrador', 'Administrador de Campaña'])) {
                return false;
            }
            
            return $user->campanas->intersect($targetUser->campanas)->isNotEmpty();
        }

        // Puede editarse a sí mismo (datos básicos)
        return $user->id === $targetUser->id;
    }

    /**
     * Determinar si el usuario puede eliminar otro usuario.
     */
    public function delete(User $user, User $targetUser): bool
    {
        if (!$user->can('usuario.eliminar')) {
            return false;
        }

        // No puede eliminarse a sí mismo
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Solo Superadmin puede eliminar
        return $user->hasRole('Superadministrador');
    }

    /**
     * Determinar si el usuario puede asignar roles.
     */
    public function assignRole(User $user, User $targetUser): bool
    {
        // Solo Superadmin puede asignar roles
        if (!$user->hasRole('Superadministrador')) {
            return false;
        }

        // No puede cambiar su propio rol
        return $user->id !== $targetUser->id;
    }

    /**
     * Determinar si el usuario puede gestionar territorios de otro usuario.
     */
    public function manageTerritories(User $user, User $targetUser): bool
    {
        // Superadmin puede gestionar todos
        if ($user->hasRole('Superadministrador')) {
            return true;
        }

        // Admin de campaña puede gestionar territorios de su campaña
        if ($user->hasRole('Administrador de Campaña')) {
            return $user->campanas->intersect($targetUser->campanas)->isNotEmpty();
        }

        return false;
    }
}
