<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Territorio;
use App\Traits\AppliesUserScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controlador CRUD para gestión administrativa de territorios.
 */
class TerritorioController extends Controller
{
    use AppliesUserScope;

    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'territory.scope', 'audit']);
    }

    /**
     * Listar territorios con paginación y filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Territorio::class);

        $user = $request->user();
        $territoriosAccesibles = $user->getAccessibleTerritoryIds();

        $query = Territorio::whereIn('id', $territoriosAccesibles)
            ->with('padre');

        // Filtros
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('nivel')) {
            $query->where('nivel', $request->nivel);
        }

        if ($request->has('padre_id')) {
            $query->where('padre_id', $request->padre_id);
        }

        if ($request->has('buscar')) {
            $query->where('nombre', 'LIKE', "%{$request->buscar}%");
        }

        // Orden
        $query->orderBy($request->input('orden_campo', 'nombre'), $request->input('orden_dir', 'asc'));

        $territorios = $query->paginate($request->input('por_pagina', 50));

        return response()->json($territorios);
    }

    /**
     * Obtener un territorio específico.
     */
    public function show(string $id): JsonResponse
    {
        $territorio = Territorio::with(['padre', 'hijos'])->findOrFail($id);
        
        $this->authorize('view', $territorio);

        // Agregar información adicional
        $territorio->loadCount('hijos');
        
        // Obtener conteo de simpatizantes
        $territorio->total_simpatizantes = $territorio->simpatizantes()->count();

        return response()->json(['data' => $territorio]);
    }

    /**
     * Crear un nuevo territorio.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Territorio::class);

        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'tipo' => ['required', Rule::in(['departamento', 'municipio', 'barrio', 'vereda', 'zona'])],
            'codigo' => 'nullable|string|max:50|unique:territorios,codigo',
            'padre_id' => 'nullable|uuid|exists:territorios,id',
            'nivel' => 'required|integer|min:1|max:5',
            'poblacion' => 'nullable|integer|min:0',
            'geom_wkt' => 'nullable|string', // Well-Known Text geometry
            'centroide_lat' => 'nullable|numeric|between:-90,90',
            'centroide_lng' => 'nullable|numeric|between:-180,180',
        ]);

        // Verificar acceso al padre si existe
        if (!empty($validated['padre_id'])) {
            if (!$request->user()->hasAccessToTerritorio($validated['padre_id'])) {
                return response()->json([
                    'message' => 'No tiene acceso al territorio padre'
                ], 403);
            }
        }

        DB::beginTransaction();
        try {
            $territorio = new Territorio($validated);

            // Procesar geometría si se proporcionó
            if (!empty($validated['geom_wkt'])) {
                $geomQuery = DB::selectOne(
                    'SELECT ST_GeomFromText(?, 4326) as geom',
                    [$validated['geom_wkt']]
                );
                $territorio->geom = $geomQuery->geom;
            }

            // Calcular centroide si se proporcionó geometría pero no centroide
            if (!empty($territorio->geom) && empty($validated['centroide_lat'])) {
                $centroide = DB::selectOne(
                    'SELECT ST_Y(ST_Centroid(?::geometry)) as lat, 
                            ST_X(ST_Centroid(?::geometry)) as lng',
                    [$territorio->geom, $territorio->geom]
                );
                $territorio->centroide = DB::raw("ST_SetSRID(ST_MakePoint({$centroide->lng}, {$centroide->lat}), 4326)");
            } elseif (!empty($validated['centroide_lat']) && !empty($validated['centroide_lng'])) {
                $territorio->centroide = DB::raw("ST_SetSRID(ST_MakePoint({$validated['centroide_lng']}, {$validated['centroide_lat']}), 4326)");
            }

            $territorio->save();

            DB::commit();

            return response()->json([
                'message' => 'Territorio creado exitosamente',
                'data' => $territorio->fresh()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al crear territorio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un territorio.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $territorio = Territorio::findOrFail($id);
        
        $this->authorize('update', $territorio);

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:200',
            'tipo' => ['sometimes', 'required', Rule::in(['departamento', 'municipio', 'barrio', 'vereda', 'zona'])],
            'codigo' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('territorios', 'codigo')->ignore($id)],
            'padre_id' => 'sometimes|nullable|uuid|exists:territorios,id',
            'nivel' => 'sometimes|required|integer|min:1|max:5',
            'poblacion' => 'sometimes|nullable|integer|min:0',
            'geom_wkt' => 'sometimes|nullable|string',
            'centroide_lat' => 'sometimes|nullable|numeric|between:-90,90',
            'centroide_lng' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        // Verificar acceso al nuevo padre si se cambió
        if (isset($validated['padre_id']) && $validated['padre_id'] !== $territorio->padre_id) {
            if (!$request->user()->hasAccessToTerritorio($validated['padre_id'])) {
                return response()->json([
                    'message' => 'No tiene acceso al territorio padre'
                ], 403);
            }
        }

        DB::beginTransaction();
        try {
            $territorio->fill($validated);

            // Actualizar geometría si se proporcionó
            if (isset($validated['geom_wkt'])) {
                $geomQuery = DB::selectOne(
                    'SELECT ST_GeomFromText(?, 4326) as geom',
                    [$validated['geom_wkt']]
                );
                $territorio->geom = $geomQuery->geom;

                // Recalcular centroide automáticamente
                $centroide = DB::selectOne(
                    'SELECT ST_Y(ST_Centroid(?::geometry)) as lat, 
                            ST_X(ST_Centroid(?::geometry)) as lng',
                    [$territorio->geom, $territorio->geom]
                );
                $territorio->centroide = DB::raw("ST_SetSRID(ST_MakePoint({$centroide->lng}, {$centroide->lat}), 4326)");
            }

            // Actualizar centroide si se proporcionó explícitamente
            if (isset($validated['centroide_lat']) && isset($validated['centroide_lng'])) {
                $territorio->centroide = DB::raw("ST_SetSRID(ST_MakePoint({$validated['centroide_lng']}, {$validated['centroide_lat']}), 4326)");
            }

            $territorio->save();

            DB::commit();

            return response()->json([
                'message' => 'Territorio actualizado exitosamente',
                'data' => $territorio->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al actualizar territorio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un territorio.
     */
    public function destroy(string $id): JsonResponse
    {
        $territorio = Territorio::findOrFail($id);
        
        $this->authorize('delete', $territorio);

        // Verificar que no tenga hijos
        if ($territorio->hijos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un territorio con hijos. Elimine primero los territorios descendientes.'
            ], 400);
        }

        // Verificar que no tenga simpatizantes
        if ($territorio->simpatizantes()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un territorio con simpatizantes registrados.'
            ], 400);
        }

        $territorio->delete();

        return response()->json([
            'message' => 'Territorio eliminado exitosamente'
        ]);
    }

    /**
     * Obtener árbol jerárquico completo de territorios.
     */
    public function arbol(Request $request): JsonResponse
    {
        $user = $request->user();
        $territoriosAccesibles = $user->getAccessibleTerritoryIds();

        // Obtener todos los territorios accesibles
        $territorios = Territorio::whereIn('id', $territoriosAccesibles)
            ->orderBy('nivel')
            ->orderBy('nombre')
            ->get();

        // Construir árbol jerárquico
        $arbol = $this->construirArbol($territorios, null);

        return response()->json(['data' => $arbol]);
    }

    /**
     * Método auxiliar para construir árbol recursivo.
     */
    private function construirArbol($territorios, $padreId): array
    {
        $rama = [];

        foreach ($territorios as $territorio) {
            if ($territorio->padre_id === $padreId) {
                $hijo = [
                    'id' => $territorio->id,
                    'nombre' => $territorio->nombre,
                    'tipo' => $territorio->tipo,
                    'codigo' => $territorio->codigo,
                    'nivel' => $territorio->nivel,
                    'hijos' => $this->construirArbol($territorios, $territorio->id),
                ];
                $rama[] = $hijo;
            }
        }

        return $rama;
    }
}
