<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exportacion;
use App\Models\Simpatizante;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

/**
 * Exportación de datos de simpatizantes con control de acceso.
 * Solo roles con permiso 'simpatizante.exportar'.
 * Toda exportación queda auditada (quién, cuándo, qué filtros, cuántas filas, motivo).
 */
class ExportacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:simpatizante.exportar');
    }

    /**
     * Crear y descargar una exportación CSV de simpatizantes.
     * Máximo 10,000 registros por exportación para roles no superadmin.
     */
    public function store(Request $request)
    {
        $request->validate([
            'motivo'       => 'required|string|min:10|max:500',
            'campana_id'   => 'nullable|exists:campanas,id',
            'territorio_id'=> 'nullable|exists:territorios,id',
            'estado'       => 'nullable|string',
            'desde'        => 'nullable|date',
            'hasta'        => 'nullable|date',
        ]);

        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('Superadministrador');
        $limite = $isSuperAdmin ? 50000 : 10000;

        // Construir query con los filtros
        $query = Simpatizante::with(['territorio:id,nombre', 'candidatos:id,nombre,cargo', 'lote:id,descripcion'])
            ->when($request->filled('campana_id'),    fn($q) => $q->where('campana_id', $request->campana_id))
            ->when($request->filled('territorio_id'), fn($q) => $q->where('territorio_id', $request->territorio_id))
            ->when($request->filled('estado'),        fn($q) => $q->where('estado', $request->estado))
            ->when($request->filled('desde'),         fn($q) => $q->where('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'),         fn($q) => $q->where('created_at', '<=', $request->hasta))
            ->orderBy('apellidos')
            ->limit($limite);

        // Aplicar scope territorial
        if (!$isSuperAdmin && $user->territorios->isNotEmpty()) {
            $idsAccesibles = $user->getAccessibleTerritoryIds();
            $query->whereIn('territorio_id', $idsAccesibles);
        }

        $simpatizantes = $query->get();

        // Registrar la exportación en auditoría ANTES de devolver el archivo
        Auditoria::registrar(
            'exportacion_creada',
            'simpatizantes',
            null,
            [
                'motivo'          => $request->motivo,
                'total_registros' => $simpatizantes->count(),
                'filtros'         => $request->only(['campana_id', 'territorio_id', 'estado', 'desde', 'hasta']),
            ]
        );

        // Guardar registro de exportación
        Exportacion::create([
            'user_id'         => $user->id,
            'tipo'            => 'simpatizantes',
            'filtros'         => $request->only(['campana_id', 'territorio_id', 'estado', 'desde', 'hasta']),
            'total_registros' => $simpatizantes->count(),
            'motivo'          => $request->motivo,
        ]);

        // Generar CSV con marca de agua
        $csv = $this->generarCsv($simpatizantes, $user->name);

        $filename = 'simpatizantes_' . Carbon::now()->format('Y-m-d_His') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Export-Count'      => $simpatizantes->count(),
            'X-Export-User'       => $user->name,
            'X-Export-Date'       => Carbon::now()->toIso8601String(),
            'X-Export-Motivo'     => substr($request->motivo, 0, 100),
        ]);
    }

    private function generarCsv($simpatizantes, string $exportadoPor): string
    {
        $output = fopen('php://temp', 'r+');

        // Marca de agua en primera línea
        fputcsv($output, [
            '# Exportado por: ' . $exportadoPor,
            'Fecha: ' . Carbon::now()->format('d/m/Y H:i'),
            'CONFIDENCIAL — Uso restringido',
        ]);

        // Encabezados de datos
        fputcsv($output, [
            'ID', 'Nombres', 'Apellidos', 'Municipio/Zona',
            'Puesto votación', 'Mesa', 'Candidatos',
            'Lote', 'Estado', 'Fecha registro',
        ]);

        foreach ($simpatizantes as $s) {
            fputcsv($output, [
                $s->id,
                $s->nombres,
                $s->apellidos,
                $s->territorio?->nombre ?? '',
                $s->puesto_votacion?->nombre ?? '',
                $s->mesa ?? '',
                $s->candidatos->pluck('nombre')->join(', '),
                $s->lote?->descripcion ?? '',
                $s->estado,
                $s->created_at ? Carbon::parse($s->created_at)->format('d/m/Y H:i') : '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        // BOM UTF-8 para compatibilidad con Excel
        return "\xEF\xBB\xBF" . $csv;
    }
}
