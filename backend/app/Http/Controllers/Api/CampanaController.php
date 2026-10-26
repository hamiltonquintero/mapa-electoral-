<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\Candidato;
use App\Models\Auditoria;
use Illuminate\Http\Request;

class CampanaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:campana.ver')->only(['index', 'show']);
        $this->middleware('permission:campana.administrar')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $campanas = Campana::withCount(['candidatos', 'simpatizantes'])
            ->when($request->filled('activa'), fn($q) => $q->where('activa', $request->activa))
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['message' => 'Campañas', 'data' => $campanas]);
    }

    public function show(Campana $campana)
    {
        $campana->load(['candidatos.territorio']);

        return response()->json(['message' => 'Campaña', 'data' => $campana]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'activa'      => 'boolean',
        ]);

        $campana = Campana::create($request->only(['nombre', 'descripcion', 'activa']));

        Auditoria::registrar('campana_creada', 'campanas', $campana->id, ['nombre' => $campana->nombre]);

        return response()->json(['message' => 'Campaña creada', 'data' => $campana], 201);
    }

    public function update(Request $request, Campana $campana)
    {
        $request->validate([
            'nombre'      => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'activa'      => 'sometimes|boolean',
        ]);

        $campana->update($request->only(['nombre', 'descripcion', 'activa']));

        Auditoria::registrar('campana_actualizada', 'campanas', $campana->id);

        return response()->json(['message' => 'Campaña actualizada', 'data' => $campana]);
    }

    public function destroy(Campana $campana)
    {
        if ($campana->simpatizantes()->count() > 0) {
            return response()->json(['message' => 'No se puede eliminar una campaña con simpatizantes registrados'], 422);
        }

        Auditoria::registrar('campana_eliminada', 'campanas', $campana->id, ['nombre' => $campana->nombre]);
        $campana->delete();

        return response()->json(['message' => 'Campaña eliminada']);
    }
}
