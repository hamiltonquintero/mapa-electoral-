<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PuestoVotacion;
use App\Models\Importacion;
use App\Models\Territorio;
use SplFileObject;
use App\Models\Campana;

class ImportCaldas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --file : optional absolute path to the CSV file.
     */
    protected $signature = 'import:caldas {--file= : Ruta del archivo CSV a importar}';

    /**
     * The console command description.
     */
    protected $description = 'Importa los puestos de votación del departamento de Caldas desde caldas_output.csv';

    /** Execute the console command. */
    public function handle(): int
    {
        $defaultPath = 'C:\\Users\\hamil\\.gemini\\antigravity-ide\\brain\\fb79e317-06be-4543-b495-d9e22a95cd1d\\scripts\\caldas_output.csv';
        $csvPath     = $this->option('file') ?? $defaultPath;

        if (!file_exists($csvPath)) {
            $this->error("El archivo CSV no existe: {$csvPath}");
            return 1;
        }

        // Obtener la campaña activa para la importación
        try {
            $campana = Campana::where('activa', true)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            $this->error('Error de conexión a la base de datos: ' . $e->getMessage());
            $this->error('Verifique que la extensión PDO MySQL esté habilitada y que las credenciales en .env sean correctas.');
            return 1;
        }
        if (! $campana) {
            $this->error('No se encontró una campaña activa.');
            return 1;
        }

        // ---------------------------------------------------------------
        // 1️⃣  Crear registro de importación
        // ---------------------------------------------------------------
        $import = Importacion::create([
            'campana_id'       => $campana->id,
            'archivo_original' => basename($csvPath),
            'archivo_ruta'     => $csvPath,
            'estado'           => 'importando',
            'filas_total'      => 0,
            'filas_ok'         => 0,
            'filas_error'      => 0,
            'errores'          => null,
            'reversible'       => true,
        ]);

        $this->info('Importación iniciada – ID: ' . $import->id);
        $this->info('Archivo: ' . $csvPath);

        $file = new SplFileObject($csvPath);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl(';');
        $file->rewind();
        $header = $file->current(); // línea de encabezado
        $file->next();

        $total   = 0;
        $ok      = 0;
        $errores = [];

        while (!$file->eof()) {
            $row = $file->current();
            $file->next();
            if ($row === null || count($row) < 2) {
                continue;
            }
            $total++;
            try {
                // ---------------------------------------------------
                // 2️⃣  Construir nombre y dirección
                // ---------------------------------------------------
                $nombreParts = array_slice($row, 7, 6); // 8‑13
                $nombre      = trim(implode(' ', $nombreParts));

                $direccionParts = array_slice($row, 13, 3); // 14‑16
                $direccion      = trim(implode(' ', $direccionParts));
                $direccion      = $direccion === '' ? null : $direccion;

                // ---------------------------------------------------
                // 3️⃣  Latitud y longitud (últimos dos campos)
                // ---------------------------------------------------
                $latitud  = $row[count($row) - 2] ?? null;
                $longitud = $row[count($row) - 1] ?? null;

                // ---------------------------------------------------
                // 4️⃣  Territorio (departamento + municipio)
                // ---------------------------------------------------
                $deptCode = $row[1] ?? null; // "09"
                $muniCode = $row[2] ?? null; // e.g. "001"
                $codigoDiv = $deptCode . $muniCode; // "09001"
                $territorio = Territorio::where('codigo_divipola', $codigoDiv)->first();
                $territorioId = $territorio ? $territorio->id : null;

                // ---------------------------------------------------
                // 5️⃣  Otros campos (por ahora valores por defecto)
                // ---------------------------------------------------
                $totalMesas          = 0;
                $codigoRegistraduria = null;

                // ---------------------------------------------------
                // 6️⃣  Guardar puesto de votación
                // ---------------------------------------------------
                PuestoVotacion::create([
                    'territorio_id'        => $territorioId,
                    'nombre'               => $nombre,
                    'direccion'            => $direccion,
                    'total_mesas'          => $totalMesas,
                    'codigo_registraduria'=> $codigoRegistraduria,
                    'latitud'              => $latitud,
                    'longitud'             => $longitud,
                    'importacion_id'       => $import->id,
                ]);
                $ok++;
            } catch (\Throwable $e) {
                $errores[] = "Línea {$total}: " . $e->getMessage();
            }
        }

        // ---------------------------------------------------------------
        // 7️⃣  Actualizar registro de importación
        // ---------------------------------------------------------------
        $import->update([
            'estado'      => empty($errores) ? 'completado' : 'error',
            'filas_total' => $total,
            'filas_ok'    => $ok,
            'filas_error' => count($errores),
            'errores'     => empty($errores) ? null : json_encode($errores, JSON_PRETTY_PRINT),
        ]);

        $this->info("Filas procesadas: {$total}");
        $this->info("Éxitos          : {$ok}");
        $this->info("Errores          : " . count($errores));
        if (!empty($errores)) {
            $this->error('Algunas filas no pudieron importarse. Consulte el campo "errores" de la tabla importaciones.');
        } else {
            $this->info('Importación completada sin errores.');
        }

        return 0;
    }
}
