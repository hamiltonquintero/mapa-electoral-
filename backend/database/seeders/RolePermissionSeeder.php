<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            // Simpatizantes
            'simpatizante.ver',
            'simpatizante.crear',
            'simpatizante.editar',
            'simpatizante.eliminar',
            'simpatizante.exportar',
            
            // Territorios
            'territorio.administrar',
            'territorio.ver',
            
            // Usuarios
            'usuario.administrar',
            'usuario.ver',
            'usuario.crear',
            'usuario.editar',
            'usuario.eliminar',
            
            // Campañas
            'campana.administrar',
            'campana.ver',
            
            // Catálogos
            'catalogo.administrar',
            'catalogo.ver',
            
            // Importación
            'importacion.realizar',
            'importacion.revertir',
            
            // Auditoría
            'auditoria.ver',
            'auditoria.exportar',
            'auditoria.gestionar',
            
            // Reportes
            'reporte.ver',
            'reporte.exportar',
            
            // Formularios
            'formulario.subir',
            'formulario.ver',
            
            // Lotes
            'lote.crear',
            'lote.ver',
            
            // Administración general
            'sistema.administrar',
            'sistema.configurar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Crear roles y asignar permisos
        $this->createSuperadminRole();
        $this->createAdminCampanaRole();
        $this->createCoordinadorMunicipalRole();
        $this->createLiderZonaRole();
        $this->createDigitadorRole();
        $this->createAuditorRole();

        $this->command?->info('✓ Roles y permisos creados exitosamente');
    }

    /**
     * Crear rol Superadministrador.
     */
    private function createSuperadminRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'Superadministrador', 'guard_name' => 'web']);
        
        // Tiene todos los permisos
        $role->syncPermissions(Permission::all());
        
        $this->command?->info('  → Superadministrador (todos los permisos)');
    }

    /**
     * Crear rol Administrador de Campaña.
     */
    private function createAdminCampanaRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'Administrador de Campaña', 'guard_name' => 'web']);
        
        $role->syncPermissions([
            // Simpatizantes
            'simpatizante.ver',
            'simpatizante.crear',
            'simpatizante.editar',
            'simpatizante.exportar',
            
            // Usuarios de su campaña
            'usuario.ver',
            'usuario.crear',
            'usuario.editar',
            
            // Campañas
            'campana.ver',
            
            // Catálogos
            'catalogo.ver',
            
            // Importación
            'importacion.realizar',
            'importacion.revertir',
            
            // Auditoría
            'auditoria.ver',
            'auditoria.exportar',
            
            // Reportes
            'reporte.ver',
            'reporte.exportar',
            
            // Formularios
            'formulario.subir',
            'formulario.ver',
            
            // Lotes
            'lote.crear',
            'lote.ver',
        ]);
        
        $this->command?->info('  → Administrador de Campaña');
    }

    /**
     * Crear rol Coordinador Municipal.
     */
    private function createCoordinadorMunicipalRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'Coordinador Municipal', 'guard_name' => 'web']);
        
        $role->syncPermissions([
            // Simpatizantes
            'simpatizante.ver',
            'simpatizante.crear',
            'simpatizante.editar',
            
            // Territorios
            'territorio.ver',
            
            // Reportes
            'reporte.ver',
            
            // Formularios
            'formulario.subir',
            'formulario.ver',
            
            // Lotes
            'lote.crear',
            'lote.ver',
        ]);
        
        $this->command?->info('  → Coordinador Municipal');
    }

    /**
     * Crear rol Líder de Zona.
     */
    private function createLiderZonaRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'Líder de Zona', 'guard_name' => 'web']);
        
        $role->syncPermissions([
            // Simpatizantes
            'simpatizante.ver',
            'simpatizante.crear',
            'simpatizante.editar',
            
            // Territorios
            'territorio.ver',
            
            // Formularios
            'formulario.subir',
            'formulario.ver',
            
            // Lotes
            'lote.crear',
            'lote.ver',
        ]);
        
        $this->command?->info('  → Líder de Zona');
    }

    /**
     * Crear rol Digitador.
     */
    private function createDigitadorRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'Digitador', 'guard_name' => 'web']);
        
        $role->syncPermissions([
            // Simpatizantes (solo crear)
            'simpatizante.crear',
            
            // Formularios
            'formulario.subir',
            
            // Lotes
            'lote.crear',
            'lote.ver',
        ]);
        
        $this->command?->info('  → Digitador');
    }

    /**
     * Crear rol Auditor.
     */
    private function createAuditorRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'Auditor', 'guard_name' => 'web']);
        
        $role->syncPermissions([
            // Solo lectura
            'simpatizante.ver',
            'territorio.ver',
            'catalogo.ver',
            'auditoria.ver',
            'reporte.ver',
            'formulario.ver',
            'lote.ver',
        ]);
        
        $this->command?->info('  → Auditor (solo lectura)');
    }
}
