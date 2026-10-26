<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Simpatizante;
use App\Models\Territorio;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'permission:reporte.ver']);
    }

    /** Tablero general: totales, evolución temporal y distribución */
    public function general(Request $request)
    {
        $campaniaId  = $request->get('campana_id');
        $candidatoId = $request->get('candidato_id');
        $dias        = (int) $request->get('dias', 30);

        $base = Simpatizante::query()
            ->when($campaniaId,  fn($q) => $q->where('campana_id', $campaniaId))
            ->when($candidatoId, fn($q) => $q->whereHas('candidatos', fn($r) => $r->where('candidatos.id', $candidatoId)));

        // Totales por estado
        $porEstado = (clone $base)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        // Capturas por día (últimos N días)
        $evolucion = (clone $base)
            ->select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', Carbon::now()->subDays($dias))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('fecha')
            ->get();

        // Top 10 municipios
        $topMunicipios = DB::table('mv_conteo_territorio as mv')
            ->join('territorios as t', 't.id', '=', 'mv.territorio_id')
            ->select('t.nombre', 'mv.total as total')
            ->where('t.tipo', 'municipio')
            ->when($campaniaId,  fn($q) => $q->where('mv.campana_id', $campaniaId))
            ->when($candidatoId, fn($q) => $q->where('mv.candidato_id', $candidatoId))
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Capturas por digitador (top 10)
        $porDigitador = (clone $base)
            ->select('creado_por', DB::raw('COUNT(*) as total'))
            ->with('creador:id,nombres,apellidos')
            ->whereNotNull('creado_por')
            ->groupBy('creado_por')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(10)
            ->get()
            ->map(fn($r) => ['usuario' => $r->creador?->nombre_completo ?? 'N/A', 'total' => $r->total]);

        return response()->json([
            'message' => 'Tablero general',
            'data'    => [
                'totales' => [
                    'total_general'    => $porEstado->sum(),
                    'validados'        => $porEstado->get('validado', 0),
                    'pendientes'       => $porEstado->get('pendiente_evidencia', 0),
                    'borradores'       => $porEstado->get('borrador', 0),
                    'rechazados'       => $porEstado->get('rechazado', 0),
                ],
                'evolucion'     => $evolucion,
                'top_municipios' => $topMunicipios,
                'por_digitador'  => $porDigitador,
            ],
        ]);
    }

    /** Tablero de calidad de datos */
    public function calidadDatos(Request $request)
    {
        $campaniaId = $request->get('campana_id');

        $base = Simpatizante::when($campaniaId, fn($q) => $q->where('campana_id', $campaniaId));

        $total = (clone $base)->count();

        $sinPuesto    = (clone $base)->whereNull('puesto_id')->count();
        $sinMesa      = (clone $base)->whereNull('mesa')->count();
        $sinZona      = (clone $base)->whereNull('territorio_id')->count();
        $sinEvidencia = (clone $base)->where('estado', 'pendiente_evidencia')->count();
        $rechazados   = (clone $base)->where('estado', 'rechazado')->count();

        return response()->json([
            'message' => 'Calidad de datos',
            'data'    => [
                'total'                 => $total,
                'sin_puesto_votacion'   => $sinPuesto,
                'sin_mesa'              => $sinMesa,
                'sin_zona_asignada'     => $sinZona,
                'pendiente_evidencia'   => $sinEvidencia,
                'rechazados'            => $rechazados,
                'porcentaje_completos'  => $total > 0
                    ? round((($total - $sinPuesto - $sinMesa - $sinEvidencia) / $total) * 100, 1)
                    : 0,
            ],
        ]);
    }

    /** Tablero de productividad de digitadores */
    public function productividad(Request $request)
    {
        $campaniaId = $request->get('campana_id');
        $dias       = (int) $request->get('dias', 30);
        $desde      = Carbon::now()->subDays($dias);

        $por_usuario = DB::table('simpatizantes as s')
            ->join('users as u', 'u.id', '=', 's.creado_por')
            ->select(
                'u.id as user_id',
                DB::raw("CONCAT(u.nombres, ' ', u.apellidos) as nombre"),
                DB::raw('COUNT(s.id) as total'),
                DB::raw('COUNT(CASE WHEN s.estado = \'validado\' THEN 1 END) as validados'),
                DB::raw('MIN(s.created_at) as primer_registro'),
                DB::raw('MAX(s.created_at) as ultimo_registro')
            )
            ->where('s.created_at', '>=', $desde)
            ->when($campaniaId, fn($q) => $q->where('s.campana_id', $campaniaId))
            ->whereNull('s.deleted_at')
            ->groupBy('u.id', 'u.nombres', 'u.apellidos')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'message' => "Productividad (últimos {$dias} días)",
            'data'    => [
                'periodo'    => ['desde' => $desde->toDateString(), 'dias' => $dias],
                'por_usuario' => $por_usuario,
            ],
        ]);
    }
}
