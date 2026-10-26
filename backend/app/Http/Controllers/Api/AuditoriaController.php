<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

/**
 * Controlador para consulta y gestión de logs de auditoría.
 * 
 * Solo accesible para roles con permiso 'auditoria.ver'.
 * Permite consultar eventos, filtrar, generar reportes y exportar logs.
 */
class AuditoriaController extends Controller
{
    /**
     * Constructor - aplicar middleware de autenticación y permisos
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:auditoria.ver');
    }

    /**
     * Listar eventos de auditoría con filtros avanzados y paginación.
     * 
     * GET /api/v1/auditoria
     * 
     * Query params:
     * - user_id: filtrar por usuario
     * - accion: filtrar por tipo de acción
     * - entidad: filtrar por entidad afectada
     * - entidad_id: filtrar por ID específico de entidad
     * - ip: filtrar por dirección IP
     * - desde: fecha inicio (Y-m-d H:i:s)
     * - hasta: fecha fin (Y-m-d H:i:s)
     * - buscar: búsqueda en acciones y detalles
     * - per_page: registros por página (default 50, max 500)
     * - order: campo de ordenamiento (default: creado_en)
     * - direction: asc|desc (default: desc)
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 50), 500);

        $query = Auditoria::with('user:id,name,email')
            ->select('auditoria.*');

        // Filtro por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtro por acción
        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        // Filtro por entidad
        if ($request->filled('entidad')) {
            $query->where('entidad', $request->entidad);
            
            if ($request->filled('entidad_id')) {
                $query->where('entidad_id', $request->entidad_id);
            }
        }

        // Filtro por IP
        if ($request->filled('ip')) {
            $query->where('ip', $request->ip);
        }

        // Filtro por rango de fechas
        if ($request->filled('desde')) {
            $desde = Carbon::parse($request->desde);
            $query->where('creado_en', '>=', $desde);
        }

        if ($request->filled('hasta')) {
            $hasta = Carbon::parse($request->hasta);
            $query->where('creado_en', '<=', $hasta);
        }

        // Búsqueda general en acción y detalles
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('accion', 'LIKE', "%{$buscar}%")
                  ->orWhere('entidad', 'LIKE', "%{$buscar}%");
                
                if (DB::connection()->getDriverName() === 'pgsql') {
                    $q->orWhereRaw("detalle::text ILIKE ?", ["%{$buscar}%"]);
                } else {
                    $q->orWhere('detalle', 'LIKE', "%{$buscar}%");
                }
            });
        }

        // Ordenamiento
        $order = $request->get('order', 'creado_en');
        $direction = $request->get('direction', 'desc');
        
        $allowedOrders = ['creado_en', 'accion', 'user_id', 'entidad', 'ip'];
        if (!in_array($order, $allowedOrders)) {
            $order = 'creado_en';
        }

        $query->orderBy($order, $direction);

        $eventos = $query->paginate($perPage);

        return response()->json([
            'message' => 'Eventos de auditoría obtenidos',
            'data' => $eventos->items(),
            'pagination' => [
                'total' => $eventos->total(),
                'per_page' => $eventos->perPage(),
                'current_page' => $eventos->currentPage(),
                'last_page' => $eventos->lastPage(),
                'from' => $eventos->firstItem(),
                'to' => $eventos->lastItem(),
            ],
        ]);
    }

    /**
     * Obtener detalle de un evento de auditoría específico.
     * 
     * GET /api/v1/auditoria/{id}
     */
    public function show(string $id)
    {
        $evento = Auditoria::with('user:id,name,email')
            ->findOrFail($id);

        return response()->json([
            'message' => 'Detalle de evento de auditoría',
            'data' => $evento,
        ]);
    }

    /**
     * Obtener resumen estadístico de eventos de auditoría.
     * 
     * GET /api/v1/auditoria/resumen
     * 
     * Retorna:
     * - Total de eventos por acción
     * - Total de eventos por usuario
     * - Total de eventos por día (últimos 30 días)
     * - Total de eventos por entidad
     */
    public function resumen(Request $request)
    {
        $dias = $request->get('dias', 30);
        $desde = Carbon::now()->subDays($dias);

        // Eventos por acción
        $porAccion = Auditoria::select('accion', DB::raw('COUNT(*) as total'))
            ->where('creado_en', '>=', $desde)
            ->groupBy('accion')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        // Eventos por usuario (top 10)
        $porUsuario = Auditoria::select('user_id', DB::raw('COUNT(*) as total'))
            ->with('user:id,name,email')
            ->where('creado_en', '>=', $desde)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user?->name,
                    'user_email' => $item->user?->email,
                    'total' => $item->total,
                ];
            });

        // Eventos por día (últimos N días)
        $porDia = Auditoria::select(
                DB::raw('DATE(creado_en) as fecha'),
                DB::raw('COUNT(*) as total')
            )
            ->where('creado_en', '>=', $desde)
            ->groupBy(DB::raw('DATE(creado_en)'))
            ->orderBy('fecha', 'asc')
            ->get();

        // Eventos por entidad
        $porEntidad = Auditoria::select('entidad', DB::raw('COUNT(*) as total'))
            ->where('creado_en', '>=', $desde)
            ->whereNotNull('entidad')
            ->groupBy('entidad')
            ->orderByDesc('total')
            ->get();

        // Total general
        $totalGeneral = Auditoria::where('creado_en', '>=', $desde)->count();

        // IPs más activas
        $ipsActivas = Auditoria::select('ip', DB::raw('COUNT(*) as total'))
            ->where('creado_en', '>=', $desde)
            ->whereNotNull('ip')
            ->groupBy('ip')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'message' => "Resumen de auditoría (últimos {$dias} días)",
            'data' => [
                'periodo' => [
                    'desde' => $desde->toDateTimeString(),
                    'hasta' => Carbon::now()->toDateTimeString(),
                    'dias' => $dias,
                ],
                'total_eventos' => $totalGeneral,
                'por_accion' => $porAccion,
                'por_usuario' => $porUsuario,
                'por_dia' => $porDia,
                'por_entidad' => $porEntidad,
                'ips_activas' => $ipsActivas,
            ],
        ]);
    }

    /**
     * Obtener actividad reciente de un usuario específico.
     * 
     * GET /api/v1/auditoria/usuario/{userId}
     */
    public function actividadUsuario(string $userId, Request $request)
    {
        $user = User::findOrFail($userId);
        
        $limite = min($request->get('limite', 50), 200);
        $dias = $request->get('dias', 30);

        $eventos = Auditoria::where('user_id', $userId)
            ->where('creado_en', '>=', Carbon::now()->subDays($dias))
            ->orderBy('creado_en', 'desc')
            ->limit($limite)
            ->get();

        // Resumen de actividad
        $resumen = [
            'total_acciones' => $eventos->count(),
            'acciones_unicas' => $eventos->pluck('accion')->unique()->count(),
            'entidades_afectadas' => $eventos->whereNotNull('entidad')->pluck('entidad')->unique()->count(),
            'ips_utilizadas' => $eventos->whereNotNull('ip')->pluck('ip')->unique()->values(),
            'primera_accion' => $eventos->last()?->creado_en,
            'ultima_accion' => $eventos->first()?->creado_en,
        ];

        return response()->json([
            'message' => "Actividad reciente del usuario {$user->name}",
            'data' => [
                'usuario' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'resumen' => $resumen,
                'eventos' => $eventos,
            ],
        ]);
    }

    /**
     * Obtener historial de cambios de una entidad específica.
     * 
     * GET /api/v1/auditoria/entidad/{entidad}/{entidadId}
     */
    public function historialEntidad(string $entidad, string $entidadId)
    {
        $eventos = Auditoria::with('user:id,name,email')
            ->where('entidad', $entidad)
            ->where('entidad_id', $entidadId)
            ->orderBy('creado_en', 'desc')
            ->get();

        return response()->json([
            'message' => "Historial de auditoría para {$entidad} #{$entidadId}",
            'data' => [
                'entidad' => $entidad,
                'entidad_id' => $entidadId,
                'total_eventos' => $eventos->count(),
                'eventos' => $eventos,
            ],
        ]);
    }

    /**
     * Obtener lista de acciones únicas registradas.
     * 
     * GET /api/v1/auditoria/acciones
     */
    public function acciones()
    {
        $acciones = Auditoria::select('accion', DB::raw('COUNT(*) as total'))
            ->groupBy('accion')
            ->orderBy('accion')
            ->get()
            ->map(function($item) {
                return [
                    'accion' => $item->accion,
                    'total' => $item->total,
                ];
            });

        return response()->json([
            'message' => 'Lista de acciones auditadas',
            'data' => $acciones,
        ]);
    }

    /**
     * Exportar logs de auditoría a CSV.
     * 
     * POST /api/v1/auditoria/exportar
     * 
     * Body:
     * - filtros (opcional): mismo formato que index()
     * - motivo (obligatorio): justificación de la exportación
     * - incluir_detalle (opcional): incluir columna detalle JSON (default: false)
     */
    public function exportar(Request $request)
    {
        // Validar permiso específico para exportar
        $this->authorize('exportar', Auditoria::class);

        $request->validate([
            'motivo' => 'required|string|min:10|max:500',
            'incluir_detalle' => 'boolean',
        ]);

        // Construir query con filtros (reutilizar lógica de index)
        $query = Auditoria::with('user:id,name,email');

        // Aplicar filtros del request
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }
        if ($request->filled('entidad')) {
            $query->where('entidad', $request->entidad);
        }
        if ($request->filled('desde')) {
            $query->where('creado_en', '>=', Carbon::parse($request->desde));
        }
        if ($request->filled('hasta')) {
            $query->where('creado_en', '<=', Carbon::parse($request->hasta));
        }

        $query->orderBy('creado_en', 'desc');

        // Limitar a 50,000 registros máximo para evitar timeout
        $eventos = $query->limit(50000)->get();

        // Registrar la exportación en auditoría
        Auditoria::registrar(
            'exportacion_auditoria',
            'auditoria',
            null,
            [
                'motivo' => $request->motivo,
                'total_registros' => $eventos->count(),
                'filtros' => $request->only(['user_id', 'accion', 'entidad', 'desde', 'hasta']),
            ]
        );

        // Generar CSV
        $incluirDetalle = $request->get('incluir_detalle', false);
        $csv = $this->generarCsv($eventos, $incluirDetalle);

        $filename = 'auditoria_' . Carbon::now()->format('Y-m-d_His') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Export-Count' => $eventos->count(),
            'X-Export-User' => auth()->user()->name,
            'X-Export-Date' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Generar contenido CSV de eventos de auditoría.
     */
    private function generarCsv($eventos, bool $incluirDetalle): string
    {
        $output = fopen('php://temp', 'r+');

        // Encabezados
        $headers = [
            'ID',
            'Fecha/Hora',
            'Usuario ID',
            'Usuario Nombre',
            'Usuario Email',
            'Rol',
            'Acción',
            'Entidad',
            'Entidad ID',
            'IP',
            'User Agent',
        ];

        if ($incluirDetalle) {
            $headers[] = 'Detalle (JSON)';
        }

        fputcsv($output, $headers);

        // Datos
        foreach ($eventos as $evento) {
            $row = [
                $evento->id,
                $evento->creado_en->format('Y-m-d H:i:s'),
                $evento->user_id,
                $evento->user?->name ?? 'N/A',
                $evento->user?->email ?? 'N/A',
                $evento->rol,
                $evento->accion,
                $evento->entidad,
                $evento->entidad_id,
                $evento->ip,
                $evento->user_agent,
            ];

            if ($incluirDetalle) {
                $row[] = json_encode($evento->detalle, JSON_UNESCAPED_UNICODE);
            }

            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        // Agregar BOM para UTF-8 (necesario para Excel)
        return "\xEF\xBB\xBF" . $csv;
    }

    /**
     * Obtener eventos críticos recientes (alertas de seguridad).
     * 
     * GET /api/v1/auditoria/alertas
     * 
     * Detecta:
     * - Múltiples intentos de login fallidos
     * - Acceso desde IPs inusuales
     * - Exportaciones masivas
     * - Supresiones de datos
     * - Cambios de permisos
     */
    public function alertas(Request $request)
    {
        $horas = $request->get('horas', 24);
        $desde = Carbon::now()->subHours($horas);

        $alertas = [];

        // 1. Login fallidos múltiples (>5 en 1 hora por usuario)
        $loginsFallidos = Auditoria::select('user_id', DB::raw('COUNT(*) as intentos'))
            ->where('accion', 'login_fallido')
            ->where('creado_en', '>=', Carbon::now()->subHour())
            ->groupBy('user_id')
            ->having('intentos', '>', 5)
            ->with('user:id,name,email')
            ->get();

        if ($loginsFallidos->count() > 0) {
            $alertas[] = [
                'tipo' => 'login_fallidos_multiples',
                'severidad' => 'alta',
                'mensaje' => 'Múltiples intentos de login fallidos detectados',
                'detalles' => $loginsFallidos,
            ];
        }

        // 2. Exportaciones grandes
        $exportaciones = Auditoria::where('accion', 'LIKE', 'exportacion_%')
            ->where('creado_en', '>=', $desde)
            ->with('user:id,name,email')
            ->get();

        if ($exportaciones->count() > 0) {
            $alertas[] = [
                'tipo' => 'exportaciones_recientes',
                'severidad' => 'media',
                'mensaje' => "Se realizaron {$exportaciones->count()} exportaciones en las últimas {$horas} horas",
                'detalles' => $exportaciones,
            ];
        }

        // 3. Supresiones de datos
        $supresiones = Auditoria::where('accion', 'LIKE', '%eliminado%')
            ->orWhere('accion', 'LIKE', '%supresion%')
            ->where('creado_en', '>=', $desde)
            ->with('user:id,name,email')
            ->get();

        if ($supresiones->count() > 0) {
            $alertas[] = [
                'tipo' => 'supresiones',
                'severidad' => 'alta',
                'mensaje' => "Se eliminaron {$supresiones->count()} registros en las últimas {$horas} horas",
                'detalles' => $supresiones,
            ];
        }

        // 4. Cambios de roles o permisos
        $cambiosPermisos = Auditoria::where('accion', 'LIKE', '%rol%')
            ->orWhere('accion', 'LIKE', '%permiso%')
            ->where('creado_en', '>=', $desde)
            ->with('user:id,name,email')
            ->get();

        if ($cambiosPermisos->count() > 0) {
            $alertas[] = [
                'tipo' => 'cambios_permisos',
                'severidad' => 'alta',
                'mensaje' => "Se realizaron {$cambiosPermisos->count()} cambios de permisos en las últimas {$horas} horas",
                'detalles' => $cambiosPermisos,
            ];
        }

        // 5. Accesos desde múltiples IPs por mismo usuario
        $ipsPorUsuario = Auditoria::select('user_id', DB::raw('COUNT(DISTINCT ip) as ips_distintas'))
            ->where('creado_en', '>=', $desde)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->having('ips_distintas', '>', 3)
            ->with('user:id,name,email')
            ->get();

        if ($ipsPorUsuario->count() > 0) {
            $alertas[] = [
                'tipo' => 'multiples_ips',
                'severidad' => 'media',
                'mensaje' => 'Usuarios accediendo desde múltiples IPs',
                'detalles' => $ipsPorUsuario,
            ];
        }

        return response()->json([
            'message' => "Alertas de seguridad (últimas {$horas} horas)",
            'data' => [
                'periodo' => [
                    'desde' => $desde->toDateTimeString(),
                    'hasta' => Carbon::now()->toDateTimeString(),
                ],
                'total_alertas' => count($alertas),
                'alertas' => $alertas,
            ],
        ]);
    }

    /**
     * Limpiar eventos de auditoría antiguos (solo Superadministrador).
     * 
     * DELETE /api/v1/auditoria/limpiar
     * 
     * Body:
     * - dias_antiguedad: eliminar eventos con más de N días (mínimo 365)
     * - confirmar: debe ser true
     */
    public function limpiar(Request $request)
    {
        // Solo Superadministrador puede limpiar auditoría
        if (!auth()->user()->hasRole('Superadministrador')) {
            return response()->json([
                'message' => 'No autorizado. Solo el Superadministrador puede limpiar auditoría.',
            ], 403);
        }

        $request->validate([
            'dias_antiguedad' => 'required|integer|min:365',
            'confirmar' => 'required|boolean|accepted',
        ]);

        $diasAntiguedad = $request->dias_antiguedad;
        $fechaLimite = Carbon::now()->subDays($diasAntiguedad);

        $cantidadEliminada = Auditoria::where('creado_en', '<', $fechaLimite)->count();

        // Registrar la limpieza ANTES de eliminar
        Auditoria::registrar(
            'limpieza_auditoria',
            'auditoria',
            null,
            [
                'dias_antiguedad' => $diasAntiguedad,
                'fecha_limite' => $fechaLimite->toDateTimeString(),
                'registros_eliminados' => $cantidadEliminada,
            ]
        );

        // Eliminar registros antiguos
        Auditoria::where('creado_en', '<', $fechaLimite)->delete();

        return response()->json([
            'message' => "Se eliminaron {$cantidadEliminada} eventos de auditoría anteriores a {$fechaLimite->format('Y-m-d')}",
            'data' => [
                'registros_eliminados' => $cantidadEliminada,
                'fecha_limite' => $fechaLimite->toDateTimeString(),
            ],
        ]);
    }
}
