<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    Route::get('/debug-db', function () {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return response()->json([
                'status' => 'connected',
                'driver' => \Illuminate\Support\Facades\DB::getDriverName(),
                'database' => \Illuminate\Support\Facades\DB::getDatabaseName(),
                'users' => \Illuminate\Support\Facades\DB::table('users')->count(),
                'campanas' => \Illuminate\Support\Facades\DB::table('campanas')->count(),
                'territorios' => \Illuminate\Support\Facades\DB::table('territorios')->count(),
                'puestos_votacion' => \Illuminate\Support\Facades\DB::table('puestos_votacion')->count(),
                'simpatizantes' => \Illuminate\Support\Facades\DB::table('simpatizantes')->count(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ], 500);
        }
    });

    Route::get('/loaded-extensions', function () {
        return response()->json([
            'commit' => 'v4-idempotent-seed',
            'pdo_drivers' => \PDO::getAvailableDrivers(),
            'loaded_extensions' => array_values(array_intersect(
                get_loaded_extensions(),
                ['pdo_pgsql', 'pgsql', 'pdo_mysql', 'gd', 'zip', 'bcmath', 'opcache']
            )),
            'db_connection' => config('database.default'),
            'database_url' => env('DATABASE_URL') ? 'configured' : 'missing',
        ]);
    });

    Route::get('/seed-database', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
            $seedOutput = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'status' => 'success',
                'migrate' => $migrateOutput,
                'seed' => $seedOutput,
                'puestos_votacion' => \Illuminate\Support\Facades\DB::table('puestos_votacion')->count(),
                'simpatizantes' => \Illuminate\Support\Facades\DB::table('simpatizantes')->count(),
                'campanas' => \Illuminate\Support\Facades\DB::table('campanas')->count(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ], 500);
        }
    });

    // Ruta fallback para peticiones sin token
    Route::get('/login', fn() => response()->json(['message' => 'No autenticado.'], 401))->name('login');

    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login'])
            ->middleware('throttle:login');
        Route::post('/verify-2fa', [App\Http\Controllers\Api\AuthController::class, 'verify2FA']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
            Route::post('/refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh']);
            Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me']);
        });
    });

    // Gestión de 2FA
    Route::prefix('2fa')->middleware('auth:sanctum')->group(function () {
        Route::get('/status', [App\Http\Controllers\Api\TwoFactorController::class, 'status']);
        Route::post('/setup', [App\Http\Controllers\Api\TwoFactorController::class, 'setup']);
        Route::post('/enable', [App\Http\Controllers\Api\TwoFactorController::class, 'enable']);
        Route::post('/disable', [App\Http\Controllers\Api\TwoFactorController::class, 'disable']);
        Route::post('/verify', [App\Http\Controllers\Api\TwoFactorController::class, 'verify']);
        Route::post('/recovery-codes/regenerate', [App\Http\Controllers\Api\TwoFactorController::class, 'regenerateRecoveryCodes']);
    });

    // Descarga pública de plantilla de importación
    Route::get('/importaciones/plantilla', [App\Http\Controllers\Api\ImportacionController::class, 'descargarPlantilla']);

    // Rutas protegidas
    Route::middleware(['auth:sanctum', '2fa', 'territory.scope', 'campaign.scope'])->group(function () {
        
        // Mapa interactivo
        Route::prefix('mapa')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\MapaController::class, 'index']);
            Route::get('/territorios/{territorio}/hijos', [App\Http\Controllers\Api\MapaController::class, 'hijos']);
            Route::get('/territorios/{territorio}/jerarquia', [App\Http\Controllers\Api\MapaController::class, 'jerarquia']);
            Route::get('/territorios/{territorio}/geojson', [App\Http\Controllers\Api\MapaController::class, 'geojson']);
            Route::post('/territorios/geojson-multiple', [App\Http\Controllers\Api\MapaController::class, 'geojsonMultiple']);
            Route::get('/territorios/{territorio}/puestos', [App\Http\Controllers\Api\MapaController::class, 'puestos']);
            Route::get('/puestos/geojson', [App\Http\Controllers\Api\MapaController::class, 'puestosGeojson']);
            Route::get('/territorios/{territorio}/estadisticas', [App\Http\Controllers\Api\MapaController::class, 'estadisticas']);
            Route::get('/buscar', [App\Http\Controllers\Api\MapaController::class, 'buscar']);
            Route::get('/territorios/{territorio}/simpatizantes', [App\Http\Controllers\Api\MapaController::class, 'simpatizantes']);
            Route::post('/simpatizantes/{id}/toggle-voto', [App\Http\Controllers\Api\MapaController::class, 'toggleVoto']);
        });

        // Administración de Territorios
        Route::middleware('audit')->group(function () {
            Route::apiResource('territorios', App\Http\Controllers\Api\TerritorioController::class)
                ->except(['index', 'show']);
            Route::get('territorios/arbol', [App\Http\Controllers\Api\TerritorioController::class, 'arbol']);
        });
        Route::get('territorios', [App\Http\Controllers\Api\TerritorioController::class, 'index']);
        Route::get('territorios/{territorio}', [App\Http\Controllers\Api\TerritorioController::class, 'show']);
        
        // Simpatizantes
        Route::prefix('simpatizantes')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\SimpatizanteController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\SimpatizanteController::class, 'store']);
            Route::post('/buscar-duplicados', [App\Http\Controllers\Api\SimpatizanteController::class, 'buscarDuplicados']);
            Route::get('/{simpatizante}', [App\Http\Controllers\Api\SimpatizanteController::class, 'show']);
            Route::put('/{simpatizante}', [App\Http\Controllers\Api\SimpatizanteController::class, 'update']);
            Route::delete('/{simpatizante}', [App\Http\Controllers\Api\SimpatizanteController::class, 'destroy']);
            Route::post('/{simpatizante}/cambiar-estado', [App\Http\Controllers\Api\SimpatizanteController::class, 'cambiarEstado']);
            Route::post('/{simpatizante}/asignar-puesto-mesa', [App\Http\Controllers\Api\SimpatizanteController::class, 'asignarPuestoMesa']);
            Route::post('/{simpatizante}/asignar-lider', [App\Http\Controllers\Api\SimpatizanteController::class, 'asignarLider']);
        });
        
        // Formularios (escaneos)
        Route::prefix('formularios')->group(function () {
            Route::post('/', [App\Http\Controllers\Api\FormularioController::class, 'store']);
            Route::get('/{formulario}', [App\Http\Controllers\Api\FormularioController::class, 'show']);
            Route::post('/{formulario}/validar', [App\Http\Controllers\Api\FormularioController::class, 'validar']);
            Route::delete('/{formulario}', [App\Http\Controllers\Api\FormularioController::class, 'destroy']);
        });
        
        // Importaciones
        Route::prefix('importaciones')->middleware('permission:importacion.realizar')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\ImportacionController::class, 'index']);
            Route::post('/validar', [App\Http\Controllers\Api\ImportacionController::class, 'validar']);
            Route::post('/', [App\Http\Controllers\Api\ImportacionController::class, 'store']);
            Route::get('/{importacion}', [App\Http\Controllers\Api\ImportacionController::class, 'show']);
            Route::post('/{importacion}/revertir', [App\Http\Controllers\Api\ImportacionController::class, 'revertir'])
                ->middleware('permission:importacion.revertir');
            Route::get('/resumen', [App\Http\Controllers\Api\ImportacionController::class, 'resumen']);
        });

        // Reportes
        Route::prefix('reportes')->middleware('permission:reporte.ver')->group(function () {
            Route::get('/general',      [App\Http\Controllers\Api\ReporteController::class, 'general']);
            Route::get('/calidad-datos',[App\Http\Controllers\Api\ReporteController::class, 'calidadDatos']);
            Route::get('/productividad',[App\Http\Controllers\Api\ReporteController::class, 'productividad']);
        });

        // Estadísticas (Vistas Materializadas)
        Route::prefix('estadisticas')->group(function () {
            Route::get('/conteos-territorio', [App\Http\Controllers\Api\EstadisticasController::class, 'conteosTerritorio']);
            Route::get('/conteos-puesto-mesa', [App\Http\Controllers\Api\EstadisticasController::class, 'conteosPuestoMesa']);
            Route::get('/generales', [App\Http\Controllers\Api\EstadisticasController::class, 'generales']);
            Route::get('/top-territorios', [App\Http\Controllers\Api\EstadisticasController::class, 'topTerritorios']);
            Route::get('/info-views', [App\Http\Controllers\Api\EstadisticasController::class, 'infoViews']);
            Route::post('/refresh-views', [App\Http\Controllers\Api\EstadisticasController::class, 'refreshViews']);
        });

        // Exportaciones
        Route::post('exportaciones', [App\Http\Controllers\Api\ExportacionController::class, 'store'])
            ->middleware('permission:simpatizante.exportar');

        // Auditoría
        Route::prefix('auditoria')->middleware('permission:auditoria.ver')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\AuditoriaController::class, 'index']);
            Route::get('/resumen', [App\Http\Controllers\Api\AuditoriaController::class, 'resumen']);
            Route::get('/acciones', [App\Http\Controllers\Api\AuditoriaController::class, 'acciones']);
            Route::get('/alertas', [App\Http\Controllers\Api\AuditoriaController::class, 'alertas']);
            Route::get('/usuario/{userId}', [App\Http\Controllers\Api\AuditoriaController::class, 'actividadUsuario']);
            Route::get('/entidad/{entidad}/{entidadId}', [App\Http\Controllers\Api\AuditoriaController::class, 'historialEntidad']);
            Route::get('/{id}', [App\Http\Controllers\Api\AuditoriaController::class, 'show']);
            Route::post('/exportar', [App\Http\Controllers\Api\AuditoriaController::class, 'exportar']);
            Route::delete('/limpiar', [App\Http\Controllers\Api\AuditoriaController::class, 'limpiar']);
        });

        // Solicitudes de titulares (ARCO — Ley 1581)
        Route::prefix('solicitudes-titulares')
            ->middleware('permission:sistema.administrar')
            ->group(function () {
                Route::get('/',    [App\Http\Controllers\Api\SolicitudTitularController::class, 'index']);
                Route::post('/',   [App\Http\Controllers\Api\SolicitudTitularController::class, 'store']);
                Route::get('/{solicitudTitular}',  [App\Http\Controllers\Api\SolicitudTitularController::class, 'show']);
                Route::put('/{solicitudTitular}',  [App\Http\Controllers\Api\SolicitudTitularController::class, 'update']);
            });

        // Administración de usuarios
        Route::prefix('usuarios')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\UsuarioController::class, 'index'])
                ->middleware('permission:usuario.ver');
            Route::get('/roles', [App\Http\Controllers\Api\UsuarioController::class, 'roles'])
                ->middleware('permission:usuario.ver');
            Route::post('/', [App\Http\Controllers\Api\UsuarioController::class, 'store'])
                ->middleware('permission:usuario.crear');
            Route::get('/{usuario}', [App\Http\Controllers\Api\UsuarioController::class, 'show'])
                ->middleware('permission:usuario.ver');
            Route::put('/{usuario}', [App\Http\Controllers\Api\UsuarioController::class, 'update'])
                ->middleware('permission:usuario.editar');
            Route::delete('/{usuario}', [App\Http\Controllers\Api\UsuarioController::class, 'destroy'])
                ->middleware('permission:usuario.eliminar');
            Route::post('/{usuario}/rol', [App\Http\Controllers\Api\UsuarioController::class, 'asignarRol'])
                ->middleware('permission:usuario.editar');
            Route::post('/{usuario}/territorios', [App\Http\Controllers\Api\UsuarioController::class, 'asignarTerritorios'])
                ->middleware('permission:usuario.editar');
        });

        // Campañas y candidatos
        Route::apiResource('campanas', App\Http\Controllers\Api\CampanaController::class);
        Route::apiResource('candidatos', App\Http\Controllers\Api\CandidatoController::class);
        Route::apiResource('puestos-votacion', App\Http\Controllers\Api\PuestoVotacionController::class);
    });
});
