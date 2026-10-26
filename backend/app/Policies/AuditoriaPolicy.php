<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Auditoria;

class AuditoriaPolicy
{
    /**
     * Ver logs de auditoría.
     */
    public function ver(User $user): bool
    {
        return $user->hasAnyPermission([
            'auditoria.ver',
            'auditoria.gestionar',
        ]);
    }

    /**
     * Exportar logs de auditoría.
     */
    public function exportar(User $user): bool
    {
        return $user->hasAnyPermission([
            'auditoria.exportar',
            'auditoria.gestionar',
        ]);
    }

    /**
     * Limpiar auditoría (solo Superadministrador).
     */
    public function limpiar(User $user): bool
    {
        return $user->hasRole('Superadministrador');
    }
}
