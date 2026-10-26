<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapa:create-user
                            {--email= : Email del usuario}
                            {--password= : Contraseña}
                            {--nombres= : Nombres}
                            {--apellidos= : Apellidos}
                            {--role= : Rol a asignar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear un nuevo usuario con rol';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== CREAR NUEVO USUARIO ===');
        $this->newLine();

        // Obtener datos
        $email = $this->option('email') ?? $this->ask('Email');
        $nombres = $this->option('nombres') ?? $this->ask('Nombres');
        $apellidos = $this->option('apellidos') ?? $this->ask('Apellidos');
        $password = $this->option('password') ?? $this->secret('Contraseña');

        // Verificar que el usuario no exista
        if (User::where('email', $email)->exists()) {
            $this->error("Ya existe un usuario con el email: {$email}");
            return 1;
        }

        // Seleccionar rol
        $roleName = $this->option('role');
        
        if (!$roleName) {
            $roles = Role::all()->pluck('name')->toArray();
            $roleName = $this->choice('Seleccione un rol', $roles);
        }

        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("Rol no encontrado: {$roleName}");
            return 1;
        }

        // Crear usuario
        $user = User::create([
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'email' => $email,
            'password' => Hash::make($password),
            'activo' => true,
        ]);

        // Asignar rol
        $user->assignRole($roleName);

        $this->newLine();
        $this->info('✓ Usuario creado exitosamente');
        $this->newLine();
        
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $user->id],
                ['Nombre', $user->nombre_completo],
                ['Email', $user->email],
                ['Rol', $roleName],
                ['Activo', $user->activo ? 'Sí' : 'No'],
            ]
        );

        $this->newLine();
        
        // Verificar si el rol requiere 2FA
        $requiredRoles = ['Superadministrador', 'Administrador de Campaña'];
        if (in_array($roleName, $requiredRoles)) {
            $this->warn('⚠ Este rol requiere autenticación 2FA obligatoria.');
            $this->line('El usuario debe configurar 2FA antes de poder acceder al sistema.');
        }

        return 0;
    }
}
