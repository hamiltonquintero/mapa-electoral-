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
        // Superadministrador
        $superadmin = User::create([
            'nombres' => 'Super',
            'apellidos' => 'Administrador',
            'email' => 'superadmin@mapa.com',
            'password' => Hash::make('password'),
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $superadmin->assignRole('Superadministrador');
        $this->command->info('  ✓ Superadministrador creado: superadmin@mapa.com / password');

        // Administrador de Campaña
        $admin = User::create([
            'nombres' => 'Administrador',
            'apellidos' => 'Campaña',
            'email' => 'admin@mapa.com',
            'password' => Hash::make('password'),
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Administrador de Campaña');
        $this->command->info('  ✓ Admin Campaña creado: admin@mapa.com / password');

        // Coordinador Municipal
        $coordinador = User::create([
            'nombres' => 'Coordinador',
            'apellidos' => 'Municipal',
            'email' => 'coordinador@mapa.com',
            'password' => Hash::make('password'),
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $coordinador->assignRole('Coordinador Municipal');
        $this->command->info('  ✓ Coordinador creado: coordinador@mapa.com / password');

        // Líder de Zona
        $lider = User::create([
            'nombres' => 'Líder',
            'apellidos' => 'Zona',
            'email' => 'lider@mapa.com',
            'password' => Hash::make('password'),
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $lider->assignRole('Líder de Zona');
        $this->command->info('  ✓ Líder creado: lider@mapa.com / password');

        // Digitador
        $digitador = User::create([
            'nombres' => 'Digitador',
            'apellidos' => 'Sistema',
            'email' => 'digitador@mapa.com',
            'password' => Hash::make('password'),
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $digitador->assignRole('Digitador');
        $this->command->info('  ✓ Digitador creado: digitador@mapa.com / password');

        // Auditor
        $auditor = User::create([
            'nombres' => 'Auditor',
            'apellidos' => 'Sistema',
            'email' => 'auditor@mapa.com',
            'password' => Hash::make('password'),
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $auditor->assignRole('Auditor');
        $this->command->info('  ✓ Auditor creado: auditor@mapa.com / password');
    }
}
