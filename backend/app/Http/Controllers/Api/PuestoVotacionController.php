<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PuestoVotacion;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PuestoVotacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:catalogo.ver')->only(['index', 'show']);
        $this->middleware('permission:catalogo.administrar')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $query = PuestoVotacion::with('territorio:id,nombre,tipo')
            ->withCount('simpatizantes');

        if ($request->filled('territorio_id')) {
            // Incluir descendientes
            $ids = \App\Models\Territorio::obtenerDescendientesIds($request->territorio_id);
            $query->whereIn('territorio_id', $ids);
        }

        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function ($q2) use ($q) {
                $q2->where('nombre', 'LIKE', "%{$q}%")
                   ->orWhere('direccion', 'LIKE', "%{$q}%");
            });
        }

        $puestos = $query->orderBy('nombre')->paginate($request->get('per_page', 50));

        return response()->json([
            'message'    => 'Puestos de votación',
            'data'       => $puestos->items(),
            'pagination' => [
                'total'        => $puestos->total(),
                'per_page'     => $puestos->perPage(),
                'current_page' => $puestos->currentPage(),
                'last_page'    => $puestos->lastPage(),
            ],
        ]);
    }

    public function show(PuestoVotacion $puestoVotacion)
    {
        $puestoVotacion->load('territorio');

        return response()->json(['message' => 'Puesto de votación', 'data' => $puestoVotacion]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'territorio_id'         => 'required|exists:territorios,id',
            'nombre'                => 'required|string|max:255',
            'direccion'             => 'nullable|string',
            'total_mesas'           => 'required|integer|min:1',
            'latitud'               => 'nullable|numeric|between:-90,90',
            'longitud'              => 'nullable|numeric|between:-180,180',
            'codigo_registraduria'  => 'nullable|string|max:50',
        ]);

        $data = $request->only(['territorio_id', 'nombre', 'direccion', 'total_mesas', 'codigo_registraduria']);

        // Convertir coordenadas a geometría PostGIS
        if ($request->filled('latitud') && $request->filled('longitud')) {
            $data['geom'] = DB::raw(
                "ST_SetSRID(ST_MakePoint({$request->longitud}, {$request->latitud}), 4326)"
            );
        }

        $puesto = PuestoVotacion::create($data);

        Auditoria::registrar('puesto_votacion_creado', 'puestos_votacion', $puesto->id, [
            'nombre' => $puesto->nombre,
        ]);

        return response()->json(['message' => 'Puesto creado', 'data' => $puesto->load('territorio')], 201);
    }

    public function update(Request $request, PuestoVotacion $puestoVotacion)
    {
        $request->validate([
            'nombre'      => 'sometimes|string|max:255',
            'direccion'   => 'nullable|string',
            'total_mesas' => 'sometimes|integer|min:1',
            'latitud'     => 'nullable|numeric|between:-90,90',
            'longitud'    => 'nullable|numeric|between:-180,180',
        ]);

        $data = $request->only(['nombre', 'direccion', 'total_mesas', 'codigo_registraduria']);

        if ($request->filled('latitud') && $request->filled('longitud')) {
            $data['geom'] = DB::raw(
                "ST_SetSRID(ST_MakePoint({$request->longitud}, {$request->latitud}), 4326)"
            );
        }

        $puestoVotacion->update($data);

        Auditoria::registrar('puesto_votacion_actualizado', 'puestos_votacion', $puestoVotacion->id);

        return response()->json(['message' => 'Puesto actualizado', 'data' => $puestoVotacion]);
    }

    public function destroy(PuestoVotacion $puestoVotacion)
    {
        if ($puestoVotacion->simpatizantes()->count() > 0) {
            return response()->json(['message' => 'El puesto tiene simpatizantes asignados'], 422);
        }

        Auditoria::registrar('puesto_votacion_eliminado', 'puestos_votacion', $puestoVotacion->id);
        $puestoVotacion->delete();

        return response()->json(['message' => 'Puesto eliminado']);
    }
}
