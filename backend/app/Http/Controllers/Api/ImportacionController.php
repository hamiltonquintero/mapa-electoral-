<?php

/**
 * Test para validar el comando import:caldas y el endpoint resumen.
 */


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Importacion;
use App\Services\ImportacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\PuestoVotacion;

/**
 * Controlador para importación masiva de simpatizantes.
 */
class ImportacionController extends Controller
{
    protected ImportacionService $importacionService;

    public function __construct(ImportacionService $importacionService)
    {
        $this->importacionService = $importacionService;
        $this->middleware(['auth:sanctum', '2fa', 'audit'])->except(['descargarPlantilla']);
        $this->middleware('permission:importacion.realizar')->only(['store', 'validar']);
        $this->middleware('permission:importacion.revertir')->only(['revertir']);
    }

    /**
     * Listar importaciones realizadas.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Importacion::with(['lote', 'creadoPor'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->has('campana_id')) {
            $query->where('campana_id', $request->campana_id);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('revertida')) {
            $query->where('revertida', $request->revertida === 'true');
        }

        $importaciones = $query->paginate($request->input('por_pagina', 20));

        return response()->json($importaciones);
    }

    /**
     * Ver detalle de una importación.
     */
    public function show(string $id): JsonResponse
    {
        $importacion = Importacion::with(['lote', 'creadoPor', 'campana'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $importacion->id,
                'nombre_archivo' => $importacion->nombre_archivo,
                'campana' => [
                    'id' => $importacion->campana->id,
                    'nombre' => $importacion->campana->nombre,
                ],
                'lote' => [
                    'id' => $importacion->lote->id,
                    'nombre' => $importacion->lote->nombre,
                ],
                'total_filas' => $importacion->total_filas,
                'filas_procesadas' => $importacion->filas_procesadas,
                'filas_exitosas' => $importacion->filas_exitosas,
                'filas_fallidas' => $importacion->filas_fallidas,
                'estado' => $importacion->estado,
                'revertida' => $importacion->revertida,
                'errores' => $importacion->errores ? json_decode($importacion->errores) : null,
                'creado_por' => [
                    'id' => $importacion->creadoPor->id,
                    'nombre_completo' => $importacion->creadoPor->nombre_completo,
                ],
                'created_at' => $importacion->created_at->toISOString(),
                'completada_en' => $importacion->completada_en?->toISOString(),
                'revertida_en' => $importacion->revertida_en?->toISOString(),
            ]
        ]);
    }

    /**
     * Validar archivo sin procesarlo.
     */
    public function validar(Request $request): JsonResponse
    {
        $request->validate([
            'archivo' => 'required|file|max:10240', // 10MB
            'campana_id' => 'nullable|exists:campanas,id',
            'territorio_default_id' => 'nullable|exists:territorios,id',
        ]);

        $campanaId = $request->input('campana_id')
            ?? $request->user()->campanas()->first()?->id
            ?? \App\Models\Campana::first()?->id;

        $territorioDefaultId = $request->input('territorio_default_id')
            ?? $request->user()->territorios()->first()?->id
            ?? \App\Models\Territorio::first()?->id;

        // Verificar permisos de acceso si se enviaron explícitamente
        if ($request->filled('campana_id') && !$request->user()->hasAccessToCampana($campanaId)) {
            return response()->json([
                'message' => 'No tiene acceso a esta campaña'
            ], 403);
        }

        if ($request->filled('territorio_default_id') && !$request->user()->hasAccessToTerritorio($territorioDefaultId)) {
            return response()->json([
                'message' => 'No tiene acceso al territorio especificado'
            ], 403);
        }

        try {
            // Guardar archivo temporalmente preservando extensión original
            $archivo = $request->file('archivo');
            $ext = strtolower($archivo->getClientOriginalExtension() ?: 'xlsx');
            $nombreTemp = 'import_val_' . uniqid() . '.' . $ext;
            $rutaTemporal = $archivo->storeAs('temp', $nombreTemp);
            $rutaCompleta = Storage::path($rutaTemporal);

            // Validar
            $resultado = $this->importacionService->validar(
                $rutaCompleta,
                (string) $campanaId,
                (string) $territorioDefaultId
            );

            // Limpiar archivo temporal
            Storage::delete($rutaTemporal);

            return response()->json([
                'data' => $resultado,
                'columnas_esperadas' => $this->importacionService->getColumnasEsperadas(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al validar archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar importación.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => 'required|file|max:10240',
            'campana_id' => 'nullable|exists:campanas,id',
            'territorio_default_id' => 'nullable|exists:territorios,id',
            'nombre_lote' => 'nullable|string|max:200',
            'saltar_duplicados' => 'nullable|boolean',
        ]);

        $campanaId = $validated['campana_id']
            ?? $request->user()->campanas()->first()?->id
            ?? \App\Models\Campana::first()?->id
            ?? 1;

        $territorioDefaultId = $validated['territorio_default_id']
            ?? $request->user()->territorios()->first()?->id
            ?? \App\Models\Territorio::first()?->id
            ?? 1;

        // Verificar permisos si se enviaron explícitamente
        if (!empty($validated['campana_id']) && !$request->user()->hasAccessToCampana($validated['campana_id'])) {
            return response()->json([
                'message' => 'No tiene acceso a esta campaña'
            ], 403);
        }

        if (!empty($validated['territorio_default_id']) && !$request->user()->hasAccessToTerritorio($validated['territorio_default_id'])) {
            return response()->json([
                'message' => 'No tiene acceso al territorio especificado'
            ], 403);
        }

        try {
            // Guardar archivo preservando la extensión original
            $archivo = $request->file('archivo');
            $ext = strtolower($archivo->getClientOriginalExtension() ?: 'xlsx');
            $nombreTemp = 'import_proc_' . uniqid() . '.' . $ext;
            $rutaTemporal = $archivo->storeAs('temp', $nombreTemp);
            $rutaCompleta = Storage::path($rutaTemporal);

            // Procesar
            $importacion = $this->importacionService->procesar(
                $rutaCompleta,
                (string) $campanaId,
                (string) $territorioDefaultId,
                $request->user()->id,
                [
                    'nombre_lote' => $validated['nombre_lote'] ?? null,
                    'saltar_duplicados' => (bool) ($validated['saltar_duplicados'] ?? false),
                ]
            );

            // Limpiar archivo temporal
            Storage::delete($rutaTemporal);

            return response()->json([
                'message' => 'Importación completada exitosamente',
                'data' => [
                    'id' => $importacion->id,
                    'total_filas' => $importacion->total_filas,
                    'filas_exitosas' => $importacion->filas_exitosas,
                    'filas_fallidas' => $importacion->filas_fallidas,
                    'estado' => $importacion->estado,
                    'lote_id' => $importacion->lote_id,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar importación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revertir una importación.
     */
    public function revertir(string $id): JsonResponse
    {
        $importacion = Importacion::findOrFail($id);

        if ($importacion->revertida) {
            return response()->json([
                'message' => 'Esta importación ya fue revertida'
            ], 422);
        }

        try {
            $eliminados = $this->importacionService->revertir($importacion);

            return response()->json([
                'message' => "Importación revertida exitosamente. Se eliminaron {$eliminados} registros.",
                'data' => [
                    'id' => $importacion->id,
                    'registros_eliminados' => $eliminados,
                    'revertida_en' => $importacion->fresh()->revertida_en->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al revertir importación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resumen de la última importación y primeros 5 puestos de votación.
     * Ruta: GET /v1/importaciones/resumen
     */
    public function resumen(): JsonResponse
    {
        $importacion = Importacion::latest()->first();
        $puestos = PuestoVotacion::orderBy('id')->take(5)->get();

        return response()->json([
            'importacion' => $importacion ? $importacion->only([
                'id',
                'nombre_archivo',
                'filas_importadas',
                'filas_exitosas',
                'filas_fallidas',
                'estado',
                'created_at',
                'completada_en',
                'revertida',
            ]) : null,
            'puestos' => $puestos->map(fn($p) => $p->only([
                'id',
                'nombre',
                'latitud',
                'longitud',
                'importacion_id',
            ])),
        ]);
    }

    /**
     * Descargar plantilla de importación.
     */
    public function descargarPlantilla(Request $request)
    {
        $columnas = array_keys($this->importacionService->getColumnasEsperadas());
        $formato = strtolower($request->input('formato', 'csv'));

        if ($formato === 'xlsx' || $request->has('xlsx')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Simpatizantes');

            // Encabezados
            $sheet->fromArray([$columnas], null, 'A1');

            // Fila de ejemplo
            $ejemplo = [
                'Juan Carlos',
                'Pérez Gómez',
                '1053800001',
                '3101234567',
                'juan.perez@ejemplo.com',
                'Calle 20 # 23-40, Barrio Centro',
                '100',
                'Registro de ejemplo'
            ];
            $sheet->fromArray([$ejemplo], null, 'A2');

            // Estilos: cabecera destacada
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            $sheet->getStyle('A1:H1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E7FF');

            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, 'plantilla_simpatizantes.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        // CSV con UTF-8 BOM para compatibilidad total con Excel en Windows
        $csv = "\xEF\xBB\xBF" . implode(',', $columnas) . "\r\n";
        $csv .= implode(',', [
            'Juan Carlos',
            'Pérez Gómez',
            '1053800001',
            '3101234567',
            'juan.perez@ejemplo.com',
            '"Calle 20 # 23-40, Barrio Centro"',
            '100',
            '"Registro de ejemplo"'
        ]) . "\r\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_simpatizantes.csv"',
        ]);
    }
}
