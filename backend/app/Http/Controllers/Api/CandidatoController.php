<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidato;
use App\Models\Auditoria;
use Illuminate\Http\Request;

class CandidatoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:campana.ver')->only(['index', 'show']);
        $this->middleware('permission:campana.administrar')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $candidatos = Candidato::with(['campana:id,nombre', 'territorio:id,nombre'])
            ->withCount('simpatizantes')
            ->when($request->filled('campana_id'), fn($q) => $q->where('campana_id', $request->campana_id))
            ->orderBy('nombre')
            ->get();

        return response()->json(['message' => 'Candidatos', 'data' => $candidatos]);
    }

    public function show(Candidato $candidato)
    {
        $candidato->load(['campana', 'territorio']);

        return response()->json(['message' => 'Candidato', 'data' => $candidato]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'campana_id'   => 'required|exists:campanas,id',
            'nombre'       => 'required|string|max:255',
            'cargo'        => 'required|in:gobernacion,alcaldia,asamblea',
            'territorio_id' => 'nullable|exists:territorios,id',
            'color'        => 'nullable|string|max:20',
        ]);

        $candidato = Candidato::create($request->only(['campana_id', 'nombre', 'cargo', 'territorio_id', 'color']));

        Auditoria::registrar('candidato_creado', 'candidatos', $candidato->id, [
            'nombre' => $candidato->nombre,
            'cargo'  => $candidato->cargo,
        ]);

        return response()->json(['message' => 'Candidato creado', 'data' => $candidato->load('campana')], 201);
    }

    public function update(Request $request, Candidato $candidato)
    {
        $request->validate([
            'nombre'       => 'sometimes|string|max:255',
            'cargo'        => 'sometimes|in:gobernacion,alcaldia,asamblea',
            'territorio_id' => 'nullable|exists:territorios,id',
            'color'        => 'nullable|string|max:20',
        ]);

        $candidato->update($request->only(['nombre', 'cargo', 'territorio_id', 'color']));

        Auditoria::registrar('candidato_actualizado', 'candidatos', $candidato->id);

        return response()->json(['message' => 'Candidato actualizado', 'data' => $candidato]);
    }

    public function destroy(Candidato $candidato)
    {
        Auditoria::registrar('candidato_eliminado', 'candidatos', $candidato->id, ['nombre' => $candidato->nombre]);
        $candidato->delete();

        return response()->json(['message' => 'Candidato eliminado']);
    }
}
