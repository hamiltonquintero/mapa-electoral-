<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PuestoVotacion;
use App\Models\Simpatizante;
use App\Models\Territorio;
use App\Traits\AppliesUserScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador para el mapa interactivo electoral.
 * 
 * Proporciona endpoints para:
 * - Navegación jerárquica de territorios (drill-down)
 * - Datos GeoJSON para renderizado en MapLibre
 * - Conteos de simpatizantes por territorio
 * - Información de puestos de votación
 */
class MapaController extends Controller
{
    use AppliesUserScope;

    /**
     * Obtener territorios raíz (nivel superior accesible por el usuario).
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $campanaId = $request->input('campana_id')
            ?? $user->campanas()->first()?->id
            ?? \App\Models\Campana::where('activa', true)->first()?->id
            ?? \App\Models\Campana::first()?->id;

        if (!$campanaId || !$user->hasAccessToCampana($campanaId)) {
            return response()->json([
                'message' => 'Debe especificar una campaña válida'
            ], 400);
        }

        // Obtener territorios accesibles del usuario
        $territoriosAccesibles = $user->getAccessibleTerritoryIds();

        // Obtener los territorios raíz (nivel más alto de los accesibles)
        $territorios = Territorio::whereIn('id', $territoriosAccesibles)
            ->with('padre')
            ->get();

        // Identificar los de nivel más alto (que no tienen padre accesible)
        $territoriosRaiz = $territorios->filter(function ($territorio) use ($territoriosAccesibles) {
            return !$territorio->padre_id || !in_array($territorio->padre_id, $territoriosAccesibles);
        });

        // Obtener conteos de simpatizantes por territorio
        $conteos = $this->obtenerConteosPorTerritorios($territoriosRaiz->pluck('id')->toArray(), (string) $campanaId);

        $data = $territoriosRaiz->map(function ($territorio) use ($conteos) {
            return [
                'id' => $territorio->id,
                'nombre' => $territorio->nombre,
                'tipo' => $territorio->tipo,
                'codigo' => $territorio->codigo_divipola,
                'nivel' => $territorio->tipo,
                'tiene_hijos' => $territorio->hijos()->exists(),
                'conteo' => $conteos[$territorio->id] ?? 0,
                'centroide' => [
                    'lat' => $territorio->centroide_lat,
                    'lng' => $territorio->centroide_lng,
                ],
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $data->count(),
            ]
        ]);
    }

    /**
     * Obtener hijos de un territorio específico.
     * 
     * @param Request $request
     * @param string $territorioId
     * @return JsonResponse
     */
    public function hijos(Request $request, string $territorioId): JsonResponse
    {
        $user = $request->user();
        $campanaId = $request->input('campana_id');

        if (!$campanaId || !$user->hasAccessToCampana($campanaId)) {
            return response()->json([
                'message' => 'Debe especificar una campaña válida'
            ], 400);
        }

        // Verificar acceso al territorio padre
        if (!$user->hasAccessToTerritorio($territorioId)) {
            return response()->json([
                'message' => 'No tiene acceso a este territorio'
            ], 403);
        }

        $territorio = Territorio::findOrFail($territorioId);

        // Obtener hijos accesibles
        $territoriosAccesibles = $user->getAccessibleTerritoryIds();
        $hijos = $territorio->hijos()
            ->whereIn('id', $territoriosAccesibles)
            ->get();

        // Obtener conteos
        $conteos = $this->obtenerConteosPorTerritorios($hijos->pluck('id')->toArray(), $campanaId);

        $data = $hijos->map(function ($hijo) use ($conteos) {
            return [
                'id' => $hijo->id,
                'nombre' => $hijo->nombre,
                'tipo' => $hijo->tipo,
                'codigo' => $hijo->codigo_divipola,
                'nivel' => $hijo->nivel,
                'padre_id' => $hijo->padre_id,
                'tiene_hijos' => $hijo->hijos()->exists(),
                'conteo' => $conteos[$hijo->id] ?? 0,
                'centroide' => $hijo->centroide_lat_lng,
            ];
        });

        return response()->json([
            'data' => $data,
            'padre' => [
                'id' => $territorio->id,
                'nombre' => $territorio->nombre,
                'tipo' => $territorio->tipo,
            ],
            'meta' => [
                'total' => $data->count(),
            ]
        ]);
    }

    /**
     * Obtener jerarquía completa (breadcrumb) de un territorio.
     * 
     * @param string $territorioId
     * @return JsonResponse
     */
    public function jerarquia(Request $request, string $territorioId): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAccessToTerritorio($territorioId)) {
            return response()->json([
                'message' => 'No tiene acceso a este territorio'
            ], 403);
        }

        // Obtener jerarquía recursivamente (versión MySQL)
        $jerarquia = [];
        $territorio = Territorio::find($territorioId);
        
        if ($territorio) {
            $jerarquia[] = [
                'id' => $territorio->id,
                'nombre' => $territorio->nombre,
                'tipo' => $territorio->tipo,
                'nivel' => 0,
            ];

            $nivel = 1;
            $padre = $territorio->padre;
            
            while ($padre) {
                array_unshift($jerarquia, [
                    'id' => $padre->id,
                    'nombre' => $padre->nombre,
                    'tipo' => $padre->tipo,
                    'nivel' => -$nivel,
                ]);
                $padre = $padre->padre;
                $nivel++;
            }
            
            // Renumerar niveles
            foreach ($jerarquia as $key => $item) {
                $jerarquia[$key]['nivel'] = $key;
            }
        }

        return response()->json(['data' => $jerarquia]);
    }

    /**
     * Obtener datos GeoJSON de un territorio para renderizado en mapa.
     * SIMPLIFICADO PARA MYSQL - Retorna punto centroide en lugar de polígono
     * 
     * @param Request $request
     * @param string $territorioId
     * @return JsonResponse
     */
    public function geojson(Request $request, string $territorioId): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAccessToTerritorio($territorioId)) {
            return response()->json([
                'message' => 'No tiene acceso a este territorio'
            ], 403);
        }

        $territorio = Territorio::findOrFail($territorioId);

        // MySQL simplificado: retornar punto centroide
        return response()->json([
            'type' => 'Feature',
            'id' => $territorio->id,
            'properties' => [
                'nombre' => $territorio->nombre,
                'tipo' => $territorio->tipo,
                'codigo' => $territorio->codigo_divipola,
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [$territorio->centroide_lng, $territorio->centroide_lat],
            ],
        ]);
    }

    /**
     * Obtener GeoJSON de múltiples territorios (para un nivel completo).
     * SIMPLIFICADO PARA MYSQL - Retorna puntos centroides
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function geojsonMultiple(Request $request): JsonResponse
    {
        $user = $request->user();
        $campanaId = $request->input('campana_id');
        $territorioIds = $request->input('ids', []);

        if (empty($territorioIds)) {
            return response()->json([
                'message' => 'Debe especificar al menos un territorio'
            ], 400);
        }

        // Filtrar solo territorios accesibles
        $territoriosAccesibles = $user->getAccessibleTerritoryIds();
        $territorioIds = array_intersect($territorioIds, $territoriosAccesibles);

        if (empty($territorioIds)) {
            return response()->json([
                'type' => 'FeatureCollection',
                'features' => []
            ]);
        }

        // Obtener conteos si se especifica campaña
        $conteos = [];
        if ($campanaId && $user->hasAccessToCampana($campanaId)) {
            $conteos = $this->obtenerConteosPorTerritorios($territorioIds, $campanaId);
        }

        // Obtener territorios
        $territorios = Territorio::whereIn('id', $territorioIds)->get();

        $features = $territorios->map(function ($territorio) use ($conteos) {
            return [
                'type' => 'Feature',
                'id' => $territorio->id,
                'properties' => [
                    'nombre' => $territorio->nombre,
                    'tipo' => $territorio->tipo,
                    'codigo' => $territorio->codigo_divipola,
                    'conteo' => $conteos[$territorio->id] ?? 0,
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$territorio->centroide_lng, $territorio->centroide_lat],
                ],
            ];
        })->toArray();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Obtener puestos de votación de un territorio.
     * 
     * @param Request $request
     * @param string $territorioId
     * @return JsonResponse
     */
    public function puestos(Request $request, string $territorioId): JsonResponse
    {
        $user = $request->user();
        $campanaId = $request->input('campana_id');

        if (!$user->hasAccessToTerritorio($territorioId)) {
            return response()->json([
                'message' => 'No tiene acceso a este territorio'
            ], 403);
        }

        $territorio = Territorio::findOrFail($territorioId);
        $descendientes = $this->getDescendientesRecursivos($territorioId);
        $subIds = array_merge([$territorioId], $descendientes);

        $query = PuestoVotacion::query();

        if ($territorio->tipo === 'departamento' || $territorio->tipo === 'municipio') {
            $query->whereIn('territorio_id', $subIds);
        } else {
            $nombreLimpio = strtoupper(trim(preg_replace('/^(comuna\s*\d+\s*-?\s*|vereda\s*|barrio\s*)/i', '', $territorio->nombre)));
            $query->where(function($q) use ($subIds, $territorio, $nombreLimpio) {
                $q->whereIn('territorio_id', $subIds);
                if (!empty($nombreLimpio) && $territorio->padre_id) {
                    $q->orWhere(function($sub) use ($territorio, $nombreLimpio) {
                        $sub->where('territorio_id', $territorio->padre_id)
                            ->whereRaw('UPPER(comuna) LIKE ?', ["%{$nombreLimpio}%"]);
                    });
                }
            });
        }

        $puestos = $query->orderBy('nombre')->get();

        // Obtener conteos por puesto si hay campaña
        $conteos = [];
        if ($campanaId && $user->hasAccessToCampana($campanaId)) {
            $conteos = Simpatizante::where('campana_id', $campanaId)
                ->whereIn('puesto_id', $puestos->pluck('id'))
                ->groupBy('puesto_id')
                ->selectRaw('puesto_id, COUNT(*) as total')
                ->pluck('total', 'puesto_id')
                ->toArray();
        }

        $data = $puestos->map(function ($puesto) use ($conteos) {
            return [
                'id' => $puesto->id,
                'nombre' => $puesto->nombre,
                'codigo' => $puesto->codigo_registraduria,
                'direccion' => $puesto->direccion,
                'total_mesas' => $puesto->total_mesas,
                'comuna' => $puesto->comuna,
                'mujeres' => $puesto->mujeres,
                'hombres' => $puesto->hombres,
                'censo_total' => $puesto->censo_total,
                'latitud' => $puesto->latitud,
                'longitud' => $puesto->longitud,
                'conteo' => $conteos[$puesto->id] ?? 0,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $data->count(),
            ]
        ]);
    }

    /**
     * Obtener puestos de votación en formato GeoJSON.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function puestosGeojson(Request $request): JsonResponse
    {
        $user = $request->user();
        $territorioId = $request->input('territorio_id');
        $campanaId = $request->input('campana_id');

        if (!$territorioId || !$user->hasAccessToTerritorio($territorioId)) {
            return response()->json([
                'message' => 'Territorio no válido o sin acceso'
            ], 403);
        }

        $puestos = PuestoVotacion::where('territorio_id', $territorioId)->get();

        // Obtener conteos
        $conteos = [];
        if ($campanaId && $user->hasAccessToCampana($campanaId)) {
            $conteos = Simpatizante::where('campana_id', $campanaId)
                ->whereIn('puesto_id', $puestos->pluck('id'))
                ->groupBy('puesto_id')
                ->selectRaw('puesto_id, COUNT(*) as total')
                ->pluck('total', 'puesto_id')
                ->toArray();
        }

        $features = $puestos->filter(fn($p) => $p->latitud && $p->longitud)
            ->map(function ($puesto) use ($conteos) {
            return [
                'type' => 'Feature',
                'id' => $puesto->id,
                'properties' => [
                    'nombre' => $puesto->nombre,
                    'codigo' => $puesto->codigo_registraduria,
                    'direccion' => $puesto->direccion,
                    'total_mesas' => $puesto->total_mesas,
                    'comuna' => $puesto->comuna,
                    'mujeres' => $puesto->mujeres,
                    'hombres' => $puesto->hombres,
                    'censo_total' => $puesto->censo_total,
                    'conteo' => $conteos[$puesto->id] ?? 0,
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $puesto->longitud,
                        (float) $puesto->latitud,
                    ],
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Obtener estadísticas de un territorio.
     * 
     * @param Request $request
     * @param string $territorioId
     * @return JsonResponse
     */
    public function estadisticas(Request $request, string $territorioId): JsonResponse
    {
        $user = $request->user();
        $campanaId = $request->input('campana_id');

        if (!$user->hasAccessToTerritorio($territorioId)) {
            return response()->json([
                'message' => 'No tiene acceso a este territorio'
            ], 403);
        }

        if (!$campanaId || !$user->hasAccessToCampana($campanaId)) {
            return response()->json([
                'message' => 'Debe especificar una campaña válida'
            ], 400);
        }

        // Obtener todos los territorios descendientes (versión MySQL)
        $descendientes = $this->getDescendientesRecursivos($territorioId);
        $territorioIds = array_merge([$territorioId], $descendientes);

        // Conteos generales
        $totalSimpatizantes = Simpatizante::where('campana_id', $campanaId)
            ->whereIn('territorio_id', $territorioIds)
            ->count();

        $porEstado = Simpatizante::where('campana_id', $campanaId)
            ->whereIn('territorio_id', $territorioIds)
            ->groupBy('estado')
            ->selectRaw('estado, COUNT(*) as total')
            ->pluck('total', 'estado');

        $conFormulario = Simpatizante::where('campana_id', $campanaId)
            ->whereIn('territorio_id', $territorioIds)
            ->whereNotNull('formulario_id')
            ->count();

        $conPuestoMesa = Simpatizante::where('campana_id', $campanaId)
            ->whereIn('territorio_id', $territorioIds)
            ->whereNotNull('puesto_id')
            ->whereNotNull('mesa')
            ->count();

        $puestosCenso = PuestoVotacion::whereIn('territorio_id', $territorioIds);
        $totalCenso = (int) $puestosCenso->sum('censo_total');
        $totalMesas = (int) $puestosCenso->sum('total_mesas');
        $totalPuestos = (int) $puestosCenso->count();

        return response()->json([
            'territorio_id' => $territorioId,
            'campana_id' => $campanaId,
            'total_simpatizantes' => $totalSimpatizantes,
            'por_estado' => $porEstado,
            'con_formulario' => $conFormulario,
            'con_puesto_mesa' => $conPuestoMesa,
            'total_censo' => $totalCenso,
            'total_mesas' => $totalMesas,
            'total_puestos' => $totalPuestos,
            'porcentaje_evidencia' => $totalSimpatizantes > 0 
                ? round(($conFormulario / $totalSimpatizantes) * 100, 2) 
                : 0,
            'porcentaje_ubicacion' => $totalSimpatizantes > 0 
                ? round(($conPuestoMesa / $totalSimpatizantes) * 100, 2) 
                : 0,
        ]);
    }

    /**
     * Buscar en el mapa por coordenadas o nombre.
     * SIMPLIFICADO PARA MYSQL - Sin búsqueda geográfica
     * 
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * Búsqueda universal rápida para Spotlight (Ctrl+K).
     * Retorna territorios, puestos de votación y simpatizantes categorizados.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function buscar(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = trim($request->input('q', ''));

        if (strlen($query) < 2) {
            return response()->json([
                'data' => [
                    'territorios' => [],
                    'puestos' => [],
                    'simpatizantes' => [],
                ]
            ]);
        }

        $territoriosAccesibles = $user->getAccessibleTerritoryIds();

        // 1. Territorios (Municipios, Comunas, Veredas, Barrios)
        $territorios = Territorio::whereIn('id', $territoriosAccesibles)
            ->where('nombre', 'LIKE', "%{$query}%")
            ->with('padre:id,nombre')
            ->limit(6)
            ->get(['id', 'nombre', 'tipo', 'codigo_divipola', 'centroide_lat', 'centroide_lng', 'padre_id'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre,
                    'tipo' => $item->tipo,
                    'padre' => $item->padre?->nombre,
                    'codigo' => $item->codigo_divipola,
                    'lat' => (float) $item->centroide_lat,
                    'lng' => (float) $item->centroide_lng,
                ];
            });

        // 2. Puestos de votación
        $puestos = PuestoVotacion::whereIn('territorio_id', $territoriosAccesibles)
            ->where(function ($q) use ($query) {
                $q->where('nombre', 'LIKE', "%{$query}%")
                  ->orWhere('direccion', 'LIKE', "%{$query}%");
            })
            ->with('territorio:id,nombre')
            ->limit(8)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'direccion' => $p->direccion,
                    'total_mesas' => $p->total_mesas,
                    'censo' => $p->censo,
                    'municipio' => $p->territorio?->nombre,
                    'lat' => (float) $p->latitud,
                    'lng' => (float) $p->longitud,
                ];
            });

        // 3. Simpatizantes
        $simpatizantes = Simpatizante::whereIn('territorio_id', $territoriosAccesibles)
            ->where(function ($q) use ($query) {
                $q->where('nombres', 'LIKE', "%{$query}%")
                  ->orWhere('apellidos', 'LIKE', "%{$query}%");
            })
            ->with(['puesto:id,nombre', 'territorio:id,nombre'])
            ->limit(6)
            ->get(['id', 'nombres', 'apellidos', 'puesto_id', 'territorio_id', 'mesa', 'ha_votado', 'estado'])
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'nombre_completo' => "{$s->nombres} {$s->apellidos}",
                    'puesto' => $s->puesto?->nombre,
                    'municipio' => $s->territorio?->nombre,
                    'mesa' => $s->mesa,
                    'ha_votado' => (bool) $s->ha_votado,
                    'estado' => $s->estado,
                ];
            });

        return response()->json([
            'data' => [
                'territorios' => $territorios,
                'puestos' => $puestos,
                'simpatizantes' => $simpatizantes,
            ]
        ]);
    }

    /**
     * Alternar estado de voto de un simpatizante (Día D).
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function toggleVoto(Request $request, string $id): JsonResponse
    {
        $simpatizante = Simpatizante::findOrFail($id);
        $simpatizante->ha_votado = !$simpatizante->ha_votado;
        $simpatizante->hora_voto = $simpatizante->ha_votado ? now() : null;
        $simpatizante->save();

        return response()->json([
            'message' => 'Estado de voto actualizado',
            'id' => $simpatizante->id,
            'ha_votado' => (bool) $simpatizante->ha_votado,
            'hora_voto' => $simpatizante->hora_voto?->format('H:i'),
        ]);
    }

    /**
     * Método auxiliar para obtener conteos de simpatizantes por territorio.
     * 
     * @param array $territorioIds
     * @param string $campanaId
     * @return array
     */
    private function obtenerConteosPorTerritorios(array $territorioIds, string $campanaId): array
    {
        if (empty($territorioIds)) {
            return [];
        }

        // Obtener descendientes de cada territorio (versión MySQL)
        $todosLosTerritorios = collect($territorioIds);

        foreach ($territorioIds as $territorioId) {
            $descendientes = $this->getDescendientesRecursivos($territorioId);
            $todosLosTerritorios = $todosLosTerritorios->merge($descendientes);
        }

        $todosLosTerritorios = $todosLosTerritorios->unique()->values()->toArray();

        // Contar simpatizantes por territorio
        $conteos = Simpatizante::where('campana_id', $campanaId)
            ->whereIn('territorio_id', $todosLosTerritorios)
            ->groupBy('territorio_id')
            ->selectRaw('territorio_id, COUNT(*) as total')
            ->pluck('total', 'territorio_id')
            ->toArray();

        // Agregar conteos de descendientes a padres
        $conteosAgregados = [];
        foreach ($territorioIds as $territorioId) {
            $descendientes = $this->getDescendientesRecursivos($territorioId);
            $descendientesIds = array_merge([$territorioId], $descendientes);
            
            $total = 0;
            foreach ($descendientesIds as $descId) {
                $total += $conteos[$descId] ?? 0;
            }
            
            $conteosAgregados[$territorioId] = $total;
        }

        return $conteosAgregados;
    }

    /**
     * Obtener descendientes de un territorio recursivamente (versión MySQL)
     */
    private function getDescendientesRecursivos(int $territorioId, array &$result = []): array
    {
        $hijos = Territorio::where('padre_id', $territorioId)->pluck('id')->toArray();
        
        foreach ($hijos as $hijo) {
            if (!in_array($hijo, $result)) {
                $result[] = $hijo;
                $this->getDescendientesRecursivos($hijo, $result);
            }
        }
        
        return $result;
    }

    /**
     * Obtener lista de simpatizantes de un territorio para mostrar en el mapa.
     * Devuelve solo datos no sensibles (nombre, estado, puesto, mesa).
     */
    public function simpatizantes(Request $request, string $territorioId): JsonResponse
    {
        $user = $request->user();
        $campanaId = $request->input('campana_id');
        $puestoId  = $request->input('puesto_id');
        $mesa      = $request->input('mesa');
        $porPagina = min((int) $request->input('por_pagina', 50), 100);

        if (!$user->hasAccessToTerritorio($territorioId)) {
            return response()->json(['message' => 'Sin acceso a este territorio'], 403);
        }

        // Obtener todos los descendientes del territorio
        $descendientes = $this->getDescendientesRecursivos((int)$territorioId);
        $territorioIds = array_merge([(int)$territorioId], $descendientes);

        $query = DB::table('simpatizantes as s')
            ->leftJoin('puestos_votacion as p', 's.puesto_id', '=', 'p.id')
            ->leftJoin('territorios as t', 's.territorio_id', '=', 't.id')
            ->whereIn('s.territorio_id', $territorioIds)
            ->whereNull('s.deleted_at')
            ->select(
                's.id',
                's.nombres',
                's.apellidos',
                's.estado',
                's.mesa',
                's.territorio_id',
                't.nombre as territorio_nombre',
                's.puesto_id',
                'p.nombre as puesto_nombre',
                'p.latitud',
                'p.longitud',
                's.created_at'
            );

        if ($campanaId) {
            $query->where('s.campana_id', $campanaId);
        }
        if ($puestoId) {
            $query->where('s.puesto_id', $puestoId);
        }
        if ($mesa) {
            $query->where('s.mesa', $mesa);
        }

        $query->orderBy('s.apellidos')->orderBy('s.nombres');

        $total  = $query->count();
        $items  = $query->paginate($porPagina);

        $data = collect($items->items())->map(fn($s) => [
            'id'                => $s->id,
            'nombre_completo'   => trim($s->nombres . ' ' . $s->apellidos),
            'nombres'           => $s->nombres,
            'apellidos'         => $s->apellidos,
            'estado'            => $s->estado,
            'mesa'              => $s->mesa,
            'territorio_id'     => $s->territorio_id,
            'territorio_nombre' => $s->territorio_nombre,
            'puesto_id'         => $s->puesto_id,
            'puesto_nombre'     => $s->puesto_nombre,
            'lat'               => $s->latitud ? (float)$s->latitud : null,
            'lng'               => $s->longitud ? (float)$s->longitud : null,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'total'         => $total,
                'pagina_actual' => $items->currentPage(),
                'total_paginas' => $items->lastPage(),
                'por_pagina'    => $porPagina,
            ],
        ]);
    }
}
