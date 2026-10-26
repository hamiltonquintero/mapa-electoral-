<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ListPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapa:list-permissions
                            {--role= : Filtrar por rol}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listar todos los permisos y roles del sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roleFilter = $this->option('role');

        $this->info('=== SISTEMA DE PERMISOS ===');
        $this->newLine();

        if ($roleFilter) {
            $this->showRolePermissions($roleFilter);
        } else {
            $this->showAllRolesAndPermissions();
        }

        return 0;
    }

    /**
     * Mostrar permisos de un rol específico.
     */
    private function showRolePermissions(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Rol no encontrado: {$roleName}");
            $this->line("\nRoles disponibles:");
            Role::all()->each(fn($r) => $this->line("  - {$r->name}"));
            return;
        }

        $this->info("Rol: {$role->name}");
        $this->newLine();

        $permissions = $role->permissions->sortBy('name');

        if ($permissions->isEmpty()) {
            $this->warn('Este rol no tiene permisos asignados');
            return;
        }

        $this->line("Permisos ({$permissions->count()}):");
        
        // Agrupar por módulo
        $grouped = $permissions->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        foreach ($grouped as $module => $perms) {
            $this->line("\n  {$module}:");
            $perms->each(fn($p) => $this->line("    - {$p->name}"));
        }
    }

    /**
     * Mostrar todos los roles y permisos.
     */
    private function showAllRolesAndPermissions(): void
    {
        $roles = Role::with('permissions')->get();

        $this->line("Total de roles: {$roles->count()}");
        $this->line("Total de permisos: " . Permission::count());
        $this->newLine();

        foreach ($roles as $role) {
            $permCount = $role->permissions->count();
            $this->info("{$role->name} ({$permCount} permisos)");
            
            // Mostrar solo los primeros 5 permisos
            $role->permissions->take(5)->each(fn($p) => $this->line("  - {$p->name}"));
            
            if ($permCount > 5) {
                $remaining = $permCount - 5;
                $this->line("  ... y {$remaining} más");
            }
            
            $this->newLine();
        }

        $this->comment("Uso: php artisan mapa:list-permissions --role=\"Nombre del Rol\"");
    }
}
