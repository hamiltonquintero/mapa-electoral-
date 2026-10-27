<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'nombres' => 'Super',
                'apellidos' => 'Administrador',
                'email' => 'superadmin@mapa.com',
                'rol' => 'Superadministrador',
            ],
            [
                'nombres' => 'Administrador',
                'apellidos' => 'Campaña',
                'email' => 'admin@mapa.com',
                'rol' => 'Administrador de Campaña',
            ],
            [
                'nombres' => 'Coordinador',
                'apellidos' => 'Municipal',
                'email' => 'coordinador@mapa.com',
                'rol' => 'Coordinador Municipal',
            ],
            [
                'nombres' => 'Líder',
                'apellidos' => 'Zona',
                'email' => 'lider@mapa.com',
                'rol' => 'Líder de Zona',
            ],
            [
                'nombres' => 'Digitador',
                'apellidos' => 'Sistema',
                'email' => 'digitador@mapa.com',
                'rol' => 'Digitador',
            ],
            [
                'nombres' => 'Auditor',
                'apellidos' => 'Sistema',
                'email' => 'auditor@mapa.com',
                'rol' => 'Auditor',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nombres' => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'password' => Hash::make('password'),
                    'activo' => true,
                    'email_verified_at' => now(),
                ]
            );

            if (!$user->hasRole($data['rol'])) {
                $user->assignRole($data['rol']);
            }
            $this->command?->info("  ✓ Usuario verificado/creado: {$data['email']}");
        }
    }
}
