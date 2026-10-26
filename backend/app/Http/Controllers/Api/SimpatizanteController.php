<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SimpatizanteResource;
use App\Models\Simpatizante;
use App\Traits\AppliesUserScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controlador para gestión de simpatizantes.
 * 
 * Incluye:
 * - CRUD completo con validaciones
 * - Detección de duplicados
 * - Filtros avanzados
 * - Cambio de estados
 * - Asignación de líder, puesto y mesa
 */
class SimpatizanteController extends Controller
{
    use AppliesUserScope;

    public function __construct()
    {
        // Los middlewares auth:sanctum, 2fa, territory.scope y campaign.scope
        // ya se aplican en el grupo de rutas en api.php — no duplicar aquí
        $this->middleware('audit')->only(['store', 'update', 'destroy']);
    }

    /**
     * Listar simpatizantes con filtros y paginación.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Simpatizante::class);

        $user = $request->user();

        // Consulta base con alcance del usuario
        $query = Simpatizante::accesiblePorUsuario($user)
            ->with(['territorio', 'puesto', 'lider', 'candidatos', 'lote']);

        // Filtros
        if ($request->has('campana_id')) {
            $query->enCampana($request->campana_id);
        }

        if ($request->has('territorio_id')) {
            $query->enTerritorio($request->territorio_id);
        }

        if ($request->has('puesto_id')) {
            $query->enPuesto($request->puesto_id);
        }

        if ($request->has('mesa')) {
            $query->where('mesa', $request->mesa);
        }

        if ($request->has('estado')) {
            $query->porEstado($request->estado);
        }

        if ($request->has('lider_id')) {
            $query->porLider($request->lider_id);
        }

        if ($request->has('candidato_id')) {
            $query->apoyaCandidato($request->candidato_id);
        }

        if ($request->has('lote_id')) {
            $query->enLote($request->lote_id);
        }

        if ($request->has('sin_puesto') && $request->sin_puesto) {
            $query->sinPuesto();
        }

        if ($request->has('sin_formulario') && $request->sin_formulario) {
            $query->sinFormulario();
        }

        // Búsqueda por nombre o cédula
        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'LIKE', "%{$buscar}%")
                  ->orWhere('apellidos', 'LIKE', "%{$buscar}%");
                
                // Si parece una cédula (solo números), buscar por hash
                if (is_numeric($buscar)) {
                    $hash = (new Simpatizante)->generateBlindIndex($buscar);
                    $q->orWhere('cedula_hash', $hash);
                }
            });
        }

        // Orden
        $ordenCampo = $request->input('orden_campo', 'created_at');
        $ordenDir = $request->input('orden_dir', 'desc');
        $query->orderBy($ordenCampo, $ordenDir);

        // Paginación
        $simpatizantes = $query->paginate($request->input('por_pagina', 50));

        return SimpatizanteResource::collection($simpatizantes)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Ver un simpatizante específico.
     */
    public function show(string $id): JsonResponse
    {
        $simpatizante = Simpatizante::with([
            'campana',
            'territorio',
            'puesto',
            'lider',
            'formulario',
            'lote',
            'candidatos',
            'creadoPor',
            'actualizadoPor',
            'historialCambios' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }
        ])->findOrFail($id);

        $this->authorize('view', $simpatizante);

        return response()->json([
            'data' => new SimpatizanteResource($simpatizante)
        ]);
    }

    /**
     * Crear un nuevo simpatizante.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Simpatizante::class);

        $validated = $request->validate([
            'campana_id' => 'required|exists:campanas,id',
            'territorio_id' => 'required|exists:territorios,id',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'cedula' => 'required|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:255',
            'puesto_id' => 'nullable|exists:puestos_votacion,id',
            'mesa' => 'nullable|integer|min:1',
            'lider_id' => 'nullable|exists:users,id',
            'candidato_ids' => 'nullable|array',
            'candidato_ids.*' => 'exists:candidatos,id',
            'observaciones' => 'nullable|string|max:500',
            'lote_id' => 'nullable|exists:lotes,id',
        ]);

        // Verificar acceso al territorio
        if (!$request->user()->hasAccessToTerritorio($validated['territorio_id'])) {
            return response()->json([
                'message' => 'No tiene acceso a este territorio'
            ], 403);
        }

        // Verificar acceso a la campaña
        if (!$request->user()->hasAccessToCampana($validated['campana_id'])) {
            return response()->json([
                'message' => 'No tiene acceso a esta campaña'
            ], 403);
        }

        // Verificar duplicados
        $duplicados = $this->buscarDuplicadosInternos($validated['cedula'], $validated['campana_id']);
        if ($duplicados->isNotEmpty()) {
            return response()->json([
                'message' => 'Ya existe un simpatizante con esta cédula en la campaña',
                'duplicados' => $duplicados
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Crear simpatizante
            $simpatizante = new Simpatizante($validated);
            $simpatizante->creado_por = $request->user()->id;
            $simpatizante->estado = 'borrador';
            
            // Cifrar campos sensibles (automático con mutators)
            $simpatizante->save();

            // Asociar candidatos si se proporcionaron
            if (!empty($validated['candidato_ids'])) {
                $simpatizante->candidatos()->sync($validated['candidato_ids']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Simpatizante creado exitosamente',
                'data' => new SimpatizanteResource($simpatizante->load([
                    'territorio', 'puesto', 'candidatos', 'lote'
                ]))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear simpatizante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un simpatizante.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $simpatizante = Simpatizante::findOrFail($id);
        
        $this->authorize('update', $simpatizante);

        $validated = $request->validate([
            'territorio_id' => 'sometimes|required|exists:territorios,id',
            'nombres' => 'sometimes|required|string|max:100',
            'apellidos' => 'sometimes|required|string|max:100',
            'cedula' => 'sometimes|required|string|max:20',
            'telefono' => 'sometimes|nullable|string|max:20',
            'correo' => 'sometimes|nullable|email|max:100',
            'direccion' => 'sometimes|nullable|string|max:255',
            'puesto_id' => 'sometimes|nullable|exists:puestos_votacion,id',
            'mesa' => 'sometimes|nullable|integer|min:1',
            'lider_id' => 'sometimes|nullable|exists:users,id',
            'candidato_ids' => 'sometimes|nullable|array',
            'candidato_ids.*' => 'exists:candidatos,id',
            'observaciones' => 'sometimes|nullable|string|max:500',
            'estado' => ['sometimes', Rule::in(['borrador', 'validado', 'pendiente_evidencia', 'rechazado'])],
        ]);

        // Verificar acceso al nuevo territorio si cambió
        if (isset($validated['territorio_id']) && $validated['territorio_id'] !== $simpatizante->territorio_id) {
            if (!$request->user()->hasAccessToTerritorio($validated['territorio_id'])) {
                return response()->json([
                    'message' => 'No tiene acceso al nuevo territorio'
                ], 403);
            }
        }

        // Si cambió la cédula, verificar duplicados
        if (isset($validated['cedula']) && $validated['cedula'] !== $simpatizante->cedula) {
            $duplicados = $this->buscarDuplicadosInternos(
                $validated['cedula'], 
                $simpatizante->campana_id,
                $id
            );
            if ($duplicados->isNotEmpty()) {
                return response()->json([
                    'message' => 'Ya existe otro simpatizante con esta cédula en la campaña',
                    'duplicados' => $duplicados
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Guardar valores anteriores para historial
            $cambios = [];
            foreach ($validated as $campo => $valor) {
                if ($campo !== 'candidato_ids' && $simpatizante->$campo !== $valor) {
                    $cambios[$campo] = [
                        'anterior' => $simpatizante->$campo,
                        'nuevo' => $valor
                    ];
                }
            }

            // Actualizar
            $simpatizante->fill($validated);
            $simpatizante->actualizado_por = $request->user()->id;
            $simpatizante->save();

            // Actualizar candidatos si se proporcionaron
            if (isset($validated['candidato_ids'])) {
                $simpatizante->candidatos()->sync($validated['candidato_ids']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Simpatizante actualizado exitosamente',
                'data' => new SimpatizanteResource($simpatizante->fresh([
                    'territorio', 'puesto', 'candidatos', 'lote'
                ]))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar simpatizante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un simpatizante (soft delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $simpatizante = Simpatizante::findOrFail($id);
        
        $this->authorize('delete', $simpatizante);

        $simpatizante->delete();

        return response()->json([
            'message' => 'Simpatizante eliminado exitosamente'
        ]);
    }

    /**
     * Buscar duplicados por cédula.
     */
    public function buscarDuplicados(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cedula' => 'required|string|max:20',
            'campana_id' => 'required|exists:campanas,id',
            'excluir_id' => 'nullable|uuid',
        ]);

        // Verificar acceso a la campaña
        if (!$request->user()->hasAccessToCampana($validated['campana_id'])) {
            return response()->json([
                'message' => 'No tiene acceso a esta campaña'
            ], 403);
        }

        $duplicados = $this->buscarDuplicadosInternos(
            $validated['cedula'],
            $validated['campana_id'],
            $validated['excluir_id'] ?? null
        );

        return response()->json([
            'encontrados' => $duplicados->count(),
            'data' => SimpatizanteResource::collection($duplicados)
        ]);
    }

    /**
     * Cambiar estado de un simpatizante.
     */
    public function cambiarEstado(Request $request, string $id): JsonResponse
    {
        $simpatizante = Simpatizante::findOrFail($id);
        
        $this->authorize('update', $simpatizante);

        $validated = $request->validate([
            'estado' => ['required', Rule::in(['borrador', 'validado', 'pendiente_evidencia', 'rechazado'])],
            'observaciones' => 'nullable|string|max:500',
        ]);

        $estadoAnterior = $simpatizante->estado;
        
        $simpatizante->estado = $validated['estado'];
        if (isset($validated['observaciones'])) {
            $simpatizante->observaciones = $validated['observaciones'];
        }
        $simpatizante->actualizado_por = $request->user()->id;
        $simpatizante->save();

        return response()->json([
            'message' => "Estado cambiado de '{$estadoAnterior}' a '{$validated['estado']}'",
            'data' => new SimpatizanteResource($simpatizante)
        ]);
    }

    /**
     * Asignar puesto y mesa a un simpatizante.
     */
    public function asignarPuestoMesa(Request $request, string $id): JsonResponse
    {
        $simpatizante = Simpatizante::findOrFail($id);
        
        $this->authorize('update', $simpatizante);

        $validated = $request->validate([
            'puesto_id' => 'required|exists:puestos_votacion,id',
            'mesa' => 'required|integer|min:1',
        ]);

        // Verificar que el puesto pertenezca al mismo territorio
        $puesto = \App\Models\PuestoVotacion::findOrFail($validated['puesto_id']);
        if ($puesto->territorio_id !== $simpatizante->territorio_id) {
            return response()->json([
                'message' => 'El puesto de votación no pertenece al territorio del simpatizante'
            ], 422);
        }

        // Verificar que la mesa exista en el puesto
        if ($validated['mesa'] > $puesto->total_mesas) {
            return response()->json([
                'message' => "La mesa {$validated['mesa']} no existe en este puesto (máximo: {$puesto->total_mesas})"
            ], 422);
        }

        $simpatizante->puesto_id = $validated['puesto_id'];
        $simpatizante->mesa = $validated['mesa'];
        $simpatizante->actualizado_por = $request->user()->id;
        $simpatizante->save();

        return response()->json([
            'message' => 'Puesto y mesa asignados exitosamente',
            'data' => new SimpatizanteResource($simpatizante->load('puesto'))
        ]);
    }

    /**
     * Asignar líder a un simpatizante.
     */
    public function asignarLider(Request $request, string $id): JsonResponse
    {
        $simpatizante = Simpatizante::findOrFail($id);
        
        $this->authorize('update', $simpatizante);

        $validated = $request->validate([
            'lider_id' => 'nullable|exists:users,id',
        ]);

        $simpatizante->lider_id = $validated['lider_id'];
        $simpatizante->actualizado_por = $request->user()->id;
        $simpatizante->save();

        $mensaje = $validated['lider_id'] 
            ? 'Líder asignado exitosamente' 
            : 'Líder removido exitosamente';

        return response()->json([
            'message' => $mensaje,
            'data' => new SimpatizanteResource($simpatizante->load('lider'))
        ]);
    }

    /**
     * Método auxiliar para buscar duplicados.
     */
    private function buscarDuplicadosInternos(string $cedula, string $campanaId, ?string $excluirId = null)
    {
        $query = Simpatizante::buscarPorCedula($cedula)
            ->where('campana_id', $campanaId)
            ->with(['territorio', 'puesto']);

        if ($excluirId) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->get();
    }
}
