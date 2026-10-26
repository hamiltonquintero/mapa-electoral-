<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración del Sistema de Mapa Electoral
    |--------------------------------------------------------------------------
    */

    'geodata' => [
        'default_srid' => env('DEFAULT_SRID', 4326),
        'map_center' => [
            'lat' => env('MAP_CENTER_LAT', 5.0689),
            'lng' => env('MAP_CENTER_LNG', -75.5174),
        ],
        'map_zoom_initial' => env('MAP_ZOOM_INITIAL', 9),
    ],

    'files' => [
        'max_upload_size' => env('MAX_UPLOAD_SIZE', 10240), // KB
        'allowed_types' => explode(',', env('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,pdf')),
        'formularios_path' => storage_path('app/formularios'),
    ],

    'materialized_views' => [
        'refresh_interval' => env('MV_REFRESH_INTERVAL', 5), // minutos
    ],

    'data_retention' => [
        'days_after_closure' => env('DATA_RETENTION_DAYS', 90),
    ],

    'roles' => [
        'superadmin' => 'Superadministrador',
        'admin_campana' => 'Administrador de Campaña',
        'coordinador_municipal' => 'Coordinador Municipal',
        'lider_zona' => 'Líder de Zona',
        'digitador' => 'Digitador',
        'auditor' => 'Auditor',
    ],

    'permissions' => [
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
        
        // Campañas
        'campana.administrar',
        'campana.ver',
        
        // Catálogos
        'catalogo.administrar',
        
        // Importación
        'importacion.realizar',
        'importacion.revertir',
        
        // Auditoría
        'auditoria.ver',
        
        // Reportes
        'reporte.ver',
        'reporte.exportar',
        
        // Administración general
        'sistema.administrar',
    ],

    'estados_simpatizante' => [
        'borrador' => 'Borrador',
        'validado' => 'Validado',
        'observado' => 'Observado',
        'retirado' => 'Retirado',
        'pendiente_evidencia' => 'Pendiente de Evidencia',
    ],

    'tipos_territorio' => [
        'departamento' => 'Departamento',
        'municipio' => 'Municipio',
        'corregimiento' => 'Corregimiento',
        'vereda' => 'Vereda',
        'barrio' => 'Barrio',
    ],

    'cargos_candidato' => [
        'gobernacion' => 'Gobernación',
        'alcaldia' => 'Alcaldía',
        'asamblea' => 'Asamblea',
    ],

];
