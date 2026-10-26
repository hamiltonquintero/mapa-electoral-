<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudTitular;
use App\Models\Simpatizante;
use App\Models\Auditoria;
use Illuminate\Http\Request;

/**
 * Gestión de solicitudes de titulares (derechos ARCO):
 * Acceso, Rectificación, Cancelación/Supresión y Oposición.
 * 
 * Cumple con la Ley 1581 de 2012 (Colombia) y requisitos del componente
 * jurídico coordinado por Yhon.
 */
class SolicitudTitularController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:sistema.administrar');
    }

    /** Listar solicitudes con filtros */
    public function index(Request $request)
    {
        $query = SolicitudTitular::with('simpatizante:id,nombres,apellidos')
            ->orderBy('creado_en', 'desc');

        if ($request->filled('tipo'))   $query->where('tipo', $request->tipo);
        if ($request->filled('estado')) $query->where('estado', $request->estado);

        // Alertar sobre solicitudes vencidas
        $vencidas = SolicitudTitular::pendienteVencida()->count();

        $solicitudes = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'message'           => 'Solicitudes de titulares',
            'data'              => $solicitudes->items(),
            'alertas_vencidas'  => $vencidas,
            'pagination'        => [
                'total'        => $solicitudes->total(),
                'per_page'     => $solicitudes->perPage(),
                'current_page' => $solicitudes->currentPage(),
                'last_page'    => $solicitudes->lastPage(),
            ],
        ]);
    }

    public function show(SolicitudTitular $solicitudTitular)
    {
        $solicitudTitular->load('simpatizante');

        return response()->json([
            'message' => 'Solicitud de titular',
            'data'    => $solicitudTitular,
        ]);
    }

    /** Registrar nueva solicitud */
    public function store(Request $request)
    {
        $request->validate([
            'tipo'           => 'required|in:consulta,rectificacion,supresion',
            'simpatizante_id'=> 'nullable|exists:simpatizantes,id',
            'referencia'     => 'nullable|string|max:500',
            'notas'          => 'nullable|string',
        ]);

        $solicitud = SolicitudTitular::create([
            'tipo'            => $request->tipo,
            'simpatizante_id' => $request->simpatizante_id,
            'estado'          => 'pendiente',
            'fecha_recepcion' => now(),
            'fecha_limite'    => now()->addDays(15), // 15 días hábiles según Ley 1581
            'resuelta_en'     => null,
            'notas'           => $request->notas,
        ]);

        Auditoria::registrar('solicitud_titular_creada', 'solicitudes_titular', $solicitud->id, [
            'tipo'            => $request->tipo,
            'simpatizante_id' => $request->simpatizante_id,
        ]);

        return response()->json([
            'message' => 'Solicitud registrada. Fecha límite de respuesta: ' . $solicitud->fecha_limite->format('d/m/Y'),
            'data'    => $solicitud,
        ], 201);
    }

    /** Actualizar estado de una solicitud */
    public function update(Request $request, SolicitudTitular $solicitudTitular)
    {
        $request->validate([
            'estado' => 'required|in:en_proceso,resuelta,rechazada',
            'notas'  => 'nullable|string',
        ]);

        $solicitudTitular->update([
            'estado'      => $request->estado,
            'notas'       => $request->notas ?? $solicitudTitular->notas,
            'resuelta_en' => in_array($request->estado, ['resuelta', 'rechazada']) ? now() : null,
        ]);

        // Si es supresión y se resuelve, ejecutar la supresión real
        if ($request->estado === 'resuelta' && $solicitudTitular->tipo === 'supresion' && $solicitudTitular->simpatizante_id) {
            $this->ejecutarSupresion($solicitudTitular->simpatizante_id);
        }

        Auditoria::registrar('solicitud_titular_actualizada', 'solicitudes_titular', $solicitudTitular->id, [
            'estado_nuevo' => $request->estado,
        ]);

        return response()->json([
            'message' => 'Solicitud actualizada',
            'data'    => $solicitudTitular->fresh(),
        ]);
    }

    /**
     * Ejecutar supresión definitiva de datos personales.
     * Elimina PII pero conserva el registro de auditoría.
     */
    private function ejecutarSupresion(string $simpatizanteId): void
    {
        $simpatizante = Simpatizante::find($simpatizanteId);
        if (!$simpatizante) return;

        Auditoria::registrar('simpatizante_suprimido', 'simpatizantes', $simpatizanteId, [
            'motivo'          => 'solicitud_titular_supresion',
            'cedula_hash'     => $simpatizante->cedula_hash,
        ]);

        // Eliminar datos personales (PII)
        $simpatizante->update([
            'cedula_cifrada'   => null,
            'cedula_hash'      => null,
            'telefono_cifrado' => null,
            'correo_cifrado'   => null,
            'direccion_cifrada'=> null,
            'nombres'          => '[SUPRIMIDO]',
            'apellidos'        => '[SUPRIMIDO]',
            'estado'           => 'retirado',
        ]);

        // Eliminar el formulario físico si existe
        if ($simpatizante->formulario_id) {
            $formulario = $simpatizante->formulario;
            if ($formulario) {
                \Illuminate\Support\Facades\Storage::delete($formulario->archivo_ruta ?? '');
                $formulario->delete();
            }
        }
    }
}
