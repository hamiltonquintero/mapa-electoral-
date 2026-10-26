<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapa:assign-role 
                            {email : Email del usuario}
                            {role : Nombre del rol}
                            {--remove : Remover el rol en lugar de asignarlo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asignar o remover un rol a un usuario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');
        $remove = $this->option('remove');

        // Buscar usuario
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario no encontrado: {$email}");
            return 1;
        }

        // Verificar que el rol existe
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Rol no encontrado: {$roleName}");
            $this->line("\nRoles disponibles:");
            Role::all()->each(fn($r) => $this->line("  - {$r->name}"));
            return 1;
        }

        // Asignar o remover rol
        if ($remove) {
            if (!$user->hasRole($roleName)) {
                $this->warn("El usuario no tiene el rol '{$roleName}'");
                return 0;
            }

            $user->removeRole($roleName);
            $this->info("✓ Rol '{$roleName}' removido de {$user->nombre_completo}");
        } else {
            if ($user->hasRole($roleName)) {
                $this->warn("El usuario ya tiene el rol '{$roleName}'");
                return 0;
            }

            $user->assignRole($roleName);
            $this->info("✓ Rol '{$roleName}' asignado a {$user->nombre_completo}");
        }

        // Mostrar roles actuales
        $this->newLine();
        $this->line("Roles actuales de {$user->nombre_completo}:");
        $user->roles->each(fn($r) => $this->line("  - {$r->name}"));

        return 0;
    }
}
