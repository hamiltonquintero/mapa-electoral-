<?php

namespace App\Services;

use App\Models\Importacion;
use App\Models\Lote;
use App\Models\Simpatizante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Servicio para importación masiva de simpatizantes desde CSV/XLSX.
 * 
 * Características:
 * - Validación de estructura y datos
 * - Detección de duplicados
 * - Normalización automática
 * - Procesamiento por lotes
 * - Reversión completa
 */
class ImportacionService
{
    /**
     * Validar archivo de importación sin procesarlo.
     * 
     * @param string $rutaArchivo Ruta al archivo CSV/XLSX
     * @param string $campanaId UUID de la campaña
     * @param string $territorioDefaultId UUID del territorio por defecto
     * @return array Resultado de validación con errores y estadísticas
     */
    public function validar(string $rutaArchivo, string $campanaId, string $territorioDefaultId): array
    {
        $datos = $this->leerArchivo($rutaArchivo);
        
        if (empty($datos)) {
            return [
                'valido' => false,
                'errores' => ['El archivo está vacío o no tiene el formato correcto'],
                'total_filas' => 0,
            ];
        }

        $errores = [];
        $advertencias = [];
        $duplicados = [];
        $filaNumero = 1; // Empezar desde 1 (header)

        foreach ($datos as $fila) {
            $filaNumero++;
            
            // Validar estructura de la fila
            $validacion = $this->validarFila($fila, $filaNumero);
            
            if (!$validacion['valido']) {
                $errores = array_merge($errores, $validacion['errores']);
            }
            
            if (!empty($validacion['advertencias'])) {
                $advertencias = array_merge($advertencias, $validacion['advertencias']);
            }

            // Verificar duplicados si la cédula es válida
            if (!empty($fila['cedula'])) {
                $existente = Simpatizante::buscarPorCedula($fila['cedula'])
                    ->where('campana_id', $campanaId)
                    ->first();
                
                if ($existente) {
                    $duplicados[] = [
                        'fila' => $filaNumero,
                        'cedula' => $fila['cedula'],
                        'nombre' => $existente->nombre_completo,
                        'territorio' => $existente->territorio->nombre,
                    ];
                }
            }
        }

        return [
            'valido' => empty($errores),
            'total_filas' => count($datos),
            'filas_total' => count($datos),
            'filas_error' => count($errores),
            'filas_ok' => max(0, count($datos) - count($errores)),
            'errores' => $errores,
            'advertencias' => $advertencias,
            'duplicados' => $duplicados,
            'estadisticas' => [
                'total' => count($datos),
                'errores' => count($errores),
                'advertencias' => count($advertencias),
                'duplicados' => count($duplicados),
                'validos' => count($datos) - count($duplicados),
            ]
        ];
    }

    /**
     * Procesar importación y crear simpatizantes.
     * 
     * @param string $rutaArchivo
     * @param string $campanaId
     * @param string $territorioDefaultId
     * @param string $usuarioId
     * @param array $opciones
     * @return Importacion
     */
    public function procesar(
        string $rutaArchivo,
        string $campanaId,
        string $territorioDefaultId,
        string $usuarioId,
        array $opciones = []
    ): Importacion {
        $datos = $this->leerArchivo($rutaArchivo);
        
        // Crear lote
        $lote = Lote::create([
            'nombre' => $opciones['nombre_lote'] ?? 'Importación ' . now()->format('Y-m-d H:i'),
            'campana_id' => $campanaId,
            'total_registros' => count($datos),
            'creado_por' => $usuarioId,
        ]);

        // Crear registro de importación
        $importacion = Importacion::create([
            'nombre_archivo' => basename($rutaArchivo),
            'campana_id' => $campanaId,
            'lote_id' => $lote->id,
            'total_filas' => count($datos),
            'filas_procesadas' => 0,
            'filas_exitosas' => 0,
            'filas_fallidas' => 0,
            'estado' => 'procesando',
            'creado_por' => $usuarioId,
        ]);

        DB::beginTransaction();
        try {
            $exitosos = 0;
            $fallidos = 0;
            $erroresDetalle = [];

            foreach ($datos as $index => $fila) {
                $filaNumero = $index + 2; // +2 porque index es 0-based y hay header
                
                try {
                    // Saltar duplicados si la opción está activada
                    if ($opciones['saltar_duplicados'] ?? false) {
                        $existente = Simpatizante::buscarPorCedula($fila['cedula'])
                            ->where('campana_id', $campanaId)
                            ->first();
                        
                        if ($existente) {
                            $fallidos++;
                            $erroresDetalle[] = [
                                'fila' => $filaNumero,
                                'error' => 'Cédula duplicada',
                            ];
                            continue;
                        }
                    }

                    // Normalizar datos
                    $datosNormalizados = $this->normalizarFila($fila, $campanaId, $territorioDefaultId);

                    // Crear simpatizante
                    $simpatizante = Simpatizante::create([
                        ...$datosNormalizados,
                        'lote_id' => $lote->id,
                        'creado_por' => $usuarioId,
                        'estado' => 'borrador',
                    ]);

                    $exitosos++;

                } catch (\Exception $e) {
                    $fallidos++;
                    $erroresDetalle[] = [
                        'fila' => $filaNumero,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Actualizar importación
            $importacion->update([
                'filas_procesadas' => count($datos),
                'filas_exitosas' => $exitosos,
                'filas_fallidas' => $fallidos,
                'estado' => 'completada',
                'errores' => !empty($erroresDetalle) ? json_encode($erroresDetalle) : null,
                'completada_en' => now(),
            ]);

            // Actualizar lote
            $lote->update([
                'registros_exitosos' => $exitosos,
                'registros_fallidos' => $fallidos,
            ]);

            DB::commit();

            return $importacion->fresh(['lote']);

        } catch (\Exception $e) {
            DB::rollBack();

            $importacion->update([
                'estado' => 'fallida',
                'errores' => json_encode([['error' => $e->getMessage()]]),
            ]);

            throw $e;
        }
    }

    /**
     * Revertir una importación (eliminar todos los simpatizantes del lote).
     * 
     * @param Importacion $importacion
     * @return int Cantidad de registros eliminados
     */
    public function revertir(Importacion $importacion): int
    {
        if ($importacion->revertida) {
            throw new \Exception('Esta importación ya fue revertida');
        }

        DB::beginTransaction();
        try {
            // Eliminar simpatizantes del lote
            $eliminados = Simpatizante::where('lote_id', $importacion->lote_id)
                ->delete();

            // Marcar como revertida
            $importacion->update([
                'revertida' => true,
                'revertida_en' => now(),
            ]);

            DB::commit();

            return $eliminados;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Leer archivo CSV o XLSX y retornar array de datos.
     * 
     * @param string $rutaArchivo
     * @return array
     */
    private function leerArchivo(string $rutaArchivo): array
    {
        $extension = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));

        // Detección automática por cabecera de archivo si la extensión no es explícita
        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            $handle = @fopen($rutaArchivo, 'rb');
            if ($handle) {
                $bytes = fread($handle, 4);
                fclose($handle);
                if ($bytes === "PK\x03\x04") {
                    $extension = 'xlsx';
                } else {
                    $extension = 'csv';
                }
            }
        }

        if ($extension === 'csv') {
            return $this->leerCSV($rutaArchivo);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->leerExcel($rutaArchivo);
        }

        return $this->leerCSV($rutaArchivo);
    }

    /**
     * Leer archivo CSV con autodetección de delimitador (, o ;) y soporte BOM.
     */
    private function leerCSV(string $rutaArchivo): array
    {
        $datos = [];
        $handle = fopen($rutaArchivo, 'r');
        
        if ($handle === false) {
            throw new \Exception('No se pudo abrir el archivo');
        }

        // Detectar delimitador inspeccionando la primera línea
        $primeraLinea = fgets($handle);
        if ($primeraLinea === false) {
            fclose($handle);
            return [];
        }
        rewind($handle);

        // Remover BOM de UTF-8 si existe
        if (substr($primeraLinea, 0, 3) === "\xEF\xBB\xBF") {
            fseek($handle, 3);
        }

        $delimitador = (substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',')) ? ';' : ',';

        // Leer header
        $header = fgetcsv($handle, 0, $delimitador);
        if ($header === false) {
            fclose($handle);
            return [];
        }

        // Normalizar header (minúsculas, sin espacios ni caracteres invisibles)
        $header = array_map(function($col) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col)));
        }, $header);

        // Leer filas
        while (($fila = fgetcsv($handle, 0, $delimitador)) !== false) {
            if (count($fila) === count($header)) {
                $datos[] = array_combine($header, $fila);
            }
        }

        fclose($handle);
        return $datos;
    }

    /**
     * Leer archivo Excel.
     */
    private function leerExcel(string $rutaArchivo): array
    {
        $spreadsheet = IOFactory::load($rutaArchivo);
        $worksheet = $spreadsheet->getActiveSheet();
        $filas = $worksheet->toArray();

        if (empty($filas)) {
            return [];
        }

        // Primera fila es header
        $header = array_map(function($col) {
            return strtolower(trim($col));
        }, array_shift($filas));

        $datos = [];
        foreach ($filas as $fila) {
            if (count($fila) === count($header)) {
                $datos[] = array_combine($header, $fila);
            }
        }

        return $datos;
    }

    /**
     * Validar una fila de datos.
     */
    private function validarFila(array $fila, int $filaNumero): array
    {
        $errores = [];
        $advertencias = [];

        // Campos requeridos
        $camposRequeridos = ['nombres', 'apellidos', 'cedula'];
        
        foreach ($camposRequeridos as $campo) {
            if (empty($fila[$campo])) {
                $errores[] = "Fila {$filaNumero}: El campo '{$campo}' es requerido";
            }
        }

        // Validar formato de cédula
        if (!empty($fila['cedula']) && !preg_match('/^[0-9]+$/', $fila['cedula'])) {
            $errores[] = "Fila {$filaNumero}: Cédula debe contener solo números";
        }

        // Validar email si se proporciona
        if (!empty($fila['correo']) && !filter_var($fila['correo'], FILTER_VALIDATE_EMAIL)) {
            $advertencias[] = "Fila {$filaNumero}: Email inválido, se omitirá";
        }

        // Validar teléfono si se proporciona
        if (!empty($fila['telefono']) && !preg_match('/^[0-9\+\-\s\(\)]+$/', $fila['telefono'])) {
            $advertencias[] = "Fila {$filaNumero}: Teléfono con formato incorrecto";
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'advertencias' => $advertencias,
        ];
    }

    /**
     * Normalizar datos de una fila.
     */
    private function normalizarFila(array $fila, string $campanaId, string $territorioDefaultId): array
    {
        return [
            'campana_id' => $campanaId,
            'territorio_id' => $fila['territorio_id'] ?? $territorioDefaultId,
            'nombres' => ucwords(strtolower(trim($fila['nombres']))),
            'apellidos' => ucwords(strtolower(trim($fila['apellidos']))),
            'cedula' => trim($fila['cedula']),
            'telefono' => !empty($fila['telefono']) ? trim($fila['telefono']) : null,
            'correo' => !empty($fila['correo']) && filter_var($fila['correo'], FILTER_VALIDATE_EMAIL) 
                ? strtolower(trim($fila['correo'])) 
                : null,
            'direccion' => !empty($fila['direccion']) ? trim($fila['direccion']) : null,
            'observaciones' => !empty($fila['observaciones']) ? trim($fila['observaciones']) : null,
        ];
    }

    /**
     * Obtener columnas esperadas en el archivo.
     */
    public function getColumnasEsperadas(): array
    {
        return [
            'nombres' => 'Requerido',
            'apellidos' => 'Requerido',
            'cedula' => 'Requerido (solo números)',
            'telefono' => 'Opcional',
            'correo' => 'Opcional (email válido)',
            'direccion' => 'Opcional',
            'territorio_id' => 'Opcional (UUID, si no se proporciona usa default)',
            'observaciones' => 'Opcional',
        ];
    }
}
