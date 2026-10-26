<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formulario;
use App\Models\Simpatizante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controlador para gestión de formularios escaneados.
 * 
 * Los formularios son evidencia física (escaneos, fotos) de
 * los documentos de identidad o planillas firmadas por simpatizantes.
 */
class FormularioController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', '2fa']);
    }

    /**
     * Subir un formulario (imagen escaneada).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'simpatizante_id' => 'required|uuid|exists:simpatizantes,id',
            'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB máx
            'tipo' => 'required|in:cedula,planilla,otro',
            'observaciones' => 'nullable|string|max:500',
        ]);

        // Verificar acceso al simpatizante
        $simpatizante = Simpatizante::findOrFail($validated['simpatizante_id']);
        
        if (!$request->user()->hasAccessToTerritorio($simpatizante->territorio_id)) {
            return response()->json([
                'message' => 'No tiene acceso al territorio del simpatizante'
            ], 403);
        }

        if (!$request->user()->hasAccessToCampana($simpatizante->campana_id)) {
            return response()->json([
                'message' => 'No tiene acceso a la campaña del simpatizante'
            ], 403);
        }

        try {
            $file = $request->file('archivo');
            
            // Generar nombre único para el archivo
            $extension = $file->getClientOriginalExtension();
            $nombreArchivo = Str::uuid() . '.' . $extension;
            
            // Organizar por campaña/año-mes
            $ruta = sprintf(
                'formularios/%s/%s/%s',
                $simpatizante->campana_id,
                now()->format('Y-m'),
                $nombreArchivo
            );
            
            // Guardar archivo (se encripta automáticamente en el storage)
            $path = $file->storeAs('', $ruta, 'private');
            
            // Crear registro de formulario
            $formulario = Formulario::create([
                'simpatizante_id' => $validated['simpatizante_id'],
                'tipo' => $validated['tipo'],
                'ruta_cifrada' => $path,
                'nombre_original' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'tamano' => $file->getSize(),
                'observaciones' => $validated['observaciones'] ?? null,
                'subido_por' => $request->user()->id,
                'estado' => 'pendiente',
            ]);
            
            // Actualizar simpatizante si no tenía formulario
            if (!$simpatizante->formulario_id) {
                $simpatizante->formulario_id = $formulario->id;
                $simpatizante->actualizado_por = $request->user()->id;
                
                // Si estaba en borrador, pasar a pendiente de evidencia
                if ($simpatizante->estado === 'borrador') {
                    $simpatizante->estado = 'pendiente_evidencia';
                }
                
                $simpatizante->save();
            }
            
            return response()->json([
                'message' => 'Formulario subido exitosamente',
                'data' => [
                    'id' => $formulario->id,
                    'tipo' => $formulario->tipo,
                    'nombre_original' => $formulario->nombre_original,
                    'tamano' => $formulario->tamano,
                    'estado' => $formulario->estado,
                    'url_temporal' => $formulario->url_temporal,
                    'created_at' => $formulario->created_at->toISOString(),
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al subir formulario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver un formulario (genera URL temporal firmada).
     */
    public function show(string $id): JsonResponse
    {
        $formulario = Formulario::with('simpatizante')->findOrFail($id);
        
        // Verificar acceso
        if (!request()->user()->hasAccessToTerritorio($formulario->simpatizante->territorio_id)) {
            return response()->json([
                'message' => 'No tiene acceso a este formulario'
            ], 403);
        }
        
        return response()->json([
            'data' => [
                'id' => $formulario->id,
                'simpatizante_id' => $formulario->simpatizante_id,
                'tipo' => $formulario->tipo,
                'nombre_original' => $formulario->nombre_original,
                'mime_type' => $formulario->mime_type,
                'tamano' => $formulario->tamano,
                'observaciones' => $formulario->observaciones,
                'estado' => $formulario->estado,
                'url_temporal' => $formulario->url_temporal,
                'subido_por' => $formulario->subido_por,
                'created_at' => $formulario->created_at->toISOString(),
                'updated_at' => $formulario->updated_at->toISOString(),
            ]
        ]);
    }

    /**
     * Validar/rechazar un formulario.
     */
    public function validar(Request $request, string $id): JsonResponse
    {
        $formulario = Formulario::with('simpatizante')->findOrFail($id);
        
        // Verificar permiso
        if (!$request->user()->can('update', $formulario->simpatizante)) {
            return response()->json([
                'message' => 'No tiene permiso para validar este formulario'
            ], 403);
        }
        
        $validated = $request->validate([
            'estado' => 'required|in:validado,rechazado',
            'observaciones' => 'nullable|string|max:500',
        ]);
        
        $formulario->estado = $validated['estado'];
        if (isset($validated['observaciones'])) {
            $formulario->observaciones = $validated['observaciones'];
        }
        $formulario->save();
        
        // Actualizar estado del simpatizante si corresponde
        if ($validated['estado'] === 'validado' && $formulario->simpatizante->estado === 'pendiente_evidencia') {
            $formulario->simpatizante->estado = 'validado';
            $formulario->simpatizante->save();
        }
        
        return response()->json([
            'message' => "Formulario {$validated['estado']} exitosamente",
            'data' => [
                'id' => $formulario->id,
                'estado' => $formulario->estado,
            ]
        ]);
    }

    /**
     * Eliminar un formulario.
     */
    public function destroy(string $id): JsonResponse
    {
        $formulario = Formulario::with('simpatizante')->findOrFail($id);
        
        // Verificar permiso
        if (!request()->user()->can('delete', $formulario->simpatizante)) {
            return response()->json([
                'message' => 'No tiene permiso para eliminar este formulario'
            ], 403);
        }
        
        try {
            // Eliminar archivo físico
            if (Storage::disk('private')->exists($formulario->ruta_cifrada)) {
                Storage::disk('private')->delete($formulario->ruta_cifrada);
            }
            
            // Si era el formulario principal del simpatizante, remover referencia
            if ($formulario->simpatizante->formulario_id === $formulario->id) {
                $formulario->simpatizante->formulario_id = null;
                $formulario->simpatizante->save();
            }
            
            $formulario->delete();
            
            return response()->json([
                'message' => 'Formulario eliminado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar formulario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
