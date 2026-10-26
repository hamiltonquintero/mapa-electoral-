<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador para estadísticas y conteos.
 * Versión MySQL: consultas directas (sin vistas materializadas PostgreSQL).
 */
class EstadisticasController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', '2fa']);
    }

    /**
     * Obtener estadísticas generales de una campaña.
     * Llamado por el store del mapa al cargar nivel raíz.
     */
    public function generales(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campana_id'   => 'nullable|exists:campanas,id',
            'candidato_id' => 'nullable|exists:candidatos,id',
        ]);

        // Si no se pasa campaña, usar la primera activa
        $campanaId = $validated['campana_id'] ?? DB::table('campanas')->where('activa', true)->value('id');

        if (!$campanaId) {
            return response()->json(['data' => $this->_emptyStats()]);
        }

        // Verificar acceso
        if (!$request->user()->hasAccessToCampana($campanaId)) {
            return response()->json(['data' => $this->_emptyStats()]);
        }

        $total      = DB::table('simpatizantes')->where('campana_id', $campanaId)->whereNull('deleted_at')->count();
        $validados  = DB::table('simpatizantes')->where('campana_id', $campanaId)->where('estado', 'validado')->whereNull('deleted_at')->count();
        $pendientes = DB::table('simpatizantes')->where('campana_id', $campanaId)->where('estado', 'borrador')->whereNull('deleted_at')->count();
        $sinPuesto  = DB::table('simpatizantes')->where('campana_id', $campanaId)->whereNull('puesto_id')->whereNull('deleted_at')->count();
        $sinMesa    = DB::table('simpatizantes')->where('campana_id', $campanaId)->whereNull('mesa')->whereNull('deleted_at')->count();
        $sinFormulario = DB::table('simpatizantes')->where('campana_id', $campanaId)->whereNull('formulario_id')->whereNull('deleted_at')->count();

        $totalTerritorios = DB::table('simpatizantes')
            ->where('campana_id', $campanaId)
            ->whereNull('deleted_at')
            ->distinct('territorio_id')
            ->count('territorio_id');

        $totalPuestos = DB::table('simpatizantes')
            ->where('campana_id', $campanaId)
            ->whereNull('deleted_at')
            ->whereNotNull('puesto_id')
            ->distinct('puesto_id')
            ->count('puesto_id');

        return response()->json([
            'data' => [
                'total_simpatizantes'       => $total,
                'total_validados'           => $validados,
                'total_pendientes'          => $pendientes,
                'total_territorios'         => $totalTerritorios,
                'total_puestos'             => $totalPuestos,
                'sin_puesto'                => $sinPuesto,
                'sin_mesa'                  => $sinMesa,
                'sin_formulario'            => $sinFormulario,
                'porcentaje_validacion'     => $total > 0 ? round(($validados / $total) * 100, 2) : 0,
                'porcentaje_con_puesto'     => $total > 0 ? round((($total - $sinPuesto) / $total) * 100, 2) : 0,
                'porcentaje_con_formulario' => $total > 0 ? round((($total - $sinFormulario) / $total) * 100, 2) : 0,
            ]
        ]);
    }

    /**
     * Conteos por territorio.
     */
    public function conteosTerritorio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campana_id'   => 'required|exists:campanas,id',
            'candidato_id' => 'nullable|exists:candidatos,id',
            'territorio_id'=> 'nullable|exists:territorios,id',
        ]);

        if (!$request->user()->hasAccessToCampana($validated['campana_id'])) {
            return response()->json(['message' => 'Sin acceso a esta campaña'], 403);
        }

        $query = DB::table('simpatizantes as s')
            ->join('territorios as t', 's.territorio_id', '=', 't.id')
            ->where('s.campana_id', $validated['campana_id'])
            ->whereNull('s.deleted_at')
            ->select(
                't.id',
                't.nombre',
                't.tipo',
                't.codigo_divipola as codigo',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN s.estado = 'validado' THEN 1 ELSE 0 END) as validados"),
                DB::raw("SUM(CASE WHEN s.estado != 'validado' THEN 1 ELSE 0 END) as pendientes")
            )
            ->groupBy('t.id', 't.nombre', 't.tipo', 't.codigo_divipola');

        if (!empty($validated['territorio_id'])) {
            $query->where('s.territorio_id', $validated['territorio_id']);
        }

        if (!$request->user()->hasRole('Superadministrador')) {
            $accesibles = $request->user()->getAccessibleTerritoryIds();
            $query->whereIn('s.territorio_id', $accesibles);
        }

        $data = $query->orderByDesc('total')->get()->map(function ($row) {
            return [
                'territorio' => [
                    'id'     => $row->id,
                    'nombre' => $row->nombre,
                    'tipo'   => $row->tipo,
                    'codigo' => $row->codigo,
                ],
                'total'     => $row->total,
                'validados' => $row->validados,
                'pendientes'=> $row->pendientes,
                'porcentaje_validacion' => $row->total > 0
                    ? round(($row->validados / $row->total) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => ['total' => $data->count(), 'total_simpatizantes' => $data->sum('total')],
        ]);
    }

    /**
     * Conteos por puesto y mesa.
     */
    public function conteosPuestoMesa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campana_id'   => 'required|exists:campanas,id',
            'puesto_id'    => 'nullable|exists:puestos_votacion,id',
            'territorio_id'=> 'nullable|exists:territorios,id',
        ]);

        if (!$request->user()->hasAccessToCampana($validated['campana_id'])) {
            return response()->json(['message' => 'Sin acceso a esta campaña'], 403);
        }

        $query = DB::table('simpatizantes as s')
            ->join('puestos_votacion as p', 's.puesto_id', '=', 'p.id')
            ->where('s.campana_id', $validated['campana_id'])
            ->whereNotNull('s.puesto_id')
            ->whereNull('s.deleted_at')
            ->select(
                'p.id as puesto_id',
                'p.nombre as puesto_nombre',
                'p.codigo_registraduria as puesto_codigo',
                'p.direccion',
                's.mesa',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN s.estado = 'validado' THEN 1 ELSE 0 END) as validados")
            )
            ->groupBy('p.id', 'p.nombre', 'p.codigo_registraduria', 'p.direccion', 's.mesa');

        if (!empty($validated['puesto_id'])) {
            $query->where('s.puesto_id', $validated['puesto_id']);
        }
        if (!empty($validated['territorio_id'])) {
            $query->where('p.territorio_id', $validated['territorio_id']);
        }

        $data = $query->orderBy('p.nombre')->orderBy('s.mesa')->get()->map(function ($row) {
            return [
                'puesto' => [
                    'id'       => $row->puesto_id,
                    'nombre'   => $row->puesto_nombre,
                    'codigo'   => $row->puesto_codigo,
                    'direccion'=> $row->direccion,
                ],
                'mesa'      => $row->mesa,
                'total'     => $row->total,
                'validados' => $row->validados,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => ['total_registros' => $data->count(), 'total_simpatizantes' => $data->sum('total')],
        ]);
    }

    /**
     * Top territorios por cantidad de simpatizantes.
     */
    public function topTerritorios(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campana_id' => 'required|exists:campanas,id',
            'limit'      => 'nullable|integer|min:1|max:50',
        ]);

        if (!$request->user()->hasAccessToCampana($validated['campana_id'])) {
            return response()->json(['message' => 'Sin acceso a esta campaña'], 403);
        }

        $limit = $validated['limit'] ?? 10;

        $query = DB::table('simpatizantes as s')
            ->join('territorios as t', 's.territorio_id', '=', 't.id')
            ->where('s.campana_id', $validated['campana_id'])
            ->whereNull('s.deleted_at')
            ->select(
                't.id', 't.nombre', 't.tipo', 't.codigo_divipola as codigo',
                DB::raw('COUNT(*) as total_simpatizantes'),
                DB::raw("SUM(CASE WHEN s.estado = 'validado' THEN 1 ELSE 0 END) as total_validados")
            )
            ->groupBy('t.id', 't.nombre', 't.tipo', 't.codigo_divipola');

        if (!$request->user()->hasRole('Superadministrador')) {
            $accesibles = $request->user()->getAccessibleTerritoryIds();
            $query->whereIn('s.territorio_id', $accesibles);
        }

        $top = $query->orderByDesc('total_simpatizantes')->limit($limit)->get();

        $data = $top->map(function ($item) {
            return [
                'territorio' => [
                    'id' => $item->id, 'nombre' => $item->nombre,
                    'tipo' => $item->tipo, 'codigo' => $item->codigo,
                ],
                'total_simpatizantes' => $item->total_simpatizantes,
                'total_validados'     => $item->total_validados,
                'porcentaje_validacion' => $item->total_simpatizantes > 0
                    ? round(($item->total_validados / $item->total_simpatizantes) * 100, 2) : 0,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Estadísticas vacías por defecto.
     */
    private function _emptyStats(): array
    {
        return [
            'total_simpatizantes' => 0, 'total_validados' => 0, 'total_pendientes' => 0,
            'total_territorios' => 0, 'total_puestos' => 0,
            'sin_puesto' => 0, 'sin_mesa' => 0, 'sin_formulario' => 0,
            'porcentaje_validacion' => 0, 'porcentaje_con_puesto' => 0, 'porcentaje_con_formulario' => 0,
        ];
    }
}
