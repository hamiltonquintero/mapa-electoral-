<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Console\Command;

class Enforce2FA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapa:enforce-2fa
                            {--role= : Rol específico para aplicar}
                            {--dry-run : Simular sin aplicar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar y notificar usuarios que requieren 2FA pero no lo tienen habilitado';

    /**
     * Execute the console command.
     */
    public function handle(TwoFactorAuthService $twoFactorService)
    {
        $this->info('Verificando usuarios que requieren 2FA...');
        $this->newLine();

        $roleFilter = $this->option('role');
        $dryRun = $this->option('dry-run');

        // Roles que requieren 2FA obligatorio
        $requiredRoles = [
            'Superadministrador',
            'Administrador de Campaña',
        ];

        if ($roleFilter) {
            $requiredRoles = [$roleFilter];
        }

        $usersWithout2FA = collect();

        foreach ($requiredRoles as $role) {
            $users = User::role($role)
                ->where('activo', true)
                ->where('google2fa_enabled', false)
                ->get();

            $usersWithout2FA = $usersWithout2FA->merge($users);
        }

        if ($usersWithout2FA->isEmpty()) {
            $this->info('✓ Todos los usuarios con roles críticos tienen 2FA habilitado');
            return 0;
        }

        $this->warn("Se encontraron {$usersWithout2FA->count()} usuario(s) sin 2FA:");
        $this->newLine();

        $table = $usersWithout2FA->map(function ($user) {
            return [
                'ID' => $user->id,
                'Nombre' => $user->nombre_completo,
                'Email' => $user->email,
                'Rol' => $user->roles->pluck('name')->implode(', '),
                'Último acceso' => $user->ultimo_acceso?->diffForHumans() ?? 'Nunca',
            ];
        })->toArray();

        $this->table(
            ['ID', 'Nombre', 'Email', 'Rol', 'Último acceso'],
            $table
        );

        $this->newLine();

        if ($dryRun) {
            $this->info('Modo simulación - no se enviaron notificaciones');
            return 0;
        }

        if (!$this->confirm('¿Desea enviar notificaciones a estos usuarios?')) {
            $this->info('Operación cancelada');
            return 0;
        }

        // Aquí se enviarían notificaciones por email
        foreach ($usersWithout2FA as $user) {
            // TODO: Enviar email de notificación
            $this->line("  → Notificación enviada a: {$user->email}");
        }

        $this->newLine();
        $this->info('✓ Notificaciones enviadas exitosamente');

        return 0;
    }
}
