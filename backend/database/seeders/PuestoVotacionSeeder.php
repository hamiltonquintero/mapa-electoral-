<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PuestoVotacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Obtener mapeo de municipios normalizados a su ID en territorios
        $territorios = DB::table('territorios')
            ->where('tipo', 'municipio')
            ->get();

        $normalizar = function (string $str): string {
            $unwanted = ['á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U', 'ñ'=>'n', 'Ñ'=>'N'];
            return strtoupper(trim(strtr($str, $unwanted)));
        };

        $muniIdMap = [];
        foreach ($territorios as $t) {
            $muniIdMap[$normalizar($t->nombre)] = $t->id;
        }

        $caldas = DB::table('territorios')->where('tipo', 'departamento')->first();
        $caldasId = $caldas ? $caldas->id : 1;

        // Limpiar puestos anteriores
        DB::table('puestos_votacion')->delete();

        // 2. Archivos SQL con los 339 puestos de Caldas
        $archivos = [
            database_path('censo_puestos_inicial.sql'),
            database_path('censo_puestos_lote2.sql'),
            database_path('censo_puestos_lote3.sql'),
            database_path('censo_puestos_lote4.sql'),
        ];

        $regex = "/\('CALDAS',\s*'([^']+)',\s*'([^']+)',\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*([^,]+),\s*('[^']*'|NULL),\s*(-?[\d.]+),\s*(-?[\d.]+)\)/i";

        $puestosAInsertar = [];
        $contador = 0;

        foreach ($archivos as $archivo) {
            if (!file_exists($archivo)) {
                continue;
            }

            $contenido = file_get_contents($archivo);
            if (preg_match_all($regex, $contenido, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $contador++;
                    $municipioRaw = $m[1];
                    $nombrePuesto = trim($m[2]);
                    $mujeres      = (int) $m[3];
                    $hombres      = (int) $m[4];
                    $totalCenso   = (int) $m[5];
                    $totalMesas   = (int) $m[6];
                    $comunaRaw    = trim($m[7]);
                    $comuna       = ($comunaRaw === 'NULL' || empty($comunaRaw)) ? null : trim($comunaRaw, "' ");
                    $direccionRaw = trim($m[8]);
                    $direccion    = ($direccionRaw === 'NULL' || empty($direccionRaw)) ? null : trim($direccionRaw, "' ");
                    $latitud      = (float) $m[9];
                    $longitud     = (float) $m[10];

                    $muniNorm = $normalizar($municipioRaw);
                    $territorioId = $muniIdMap[$muniNorm] ?? $caldasId;

                    $codigo = sprintf("PV-CAL-%03d", $contador);

                    $puestosAInsertar[] = [
                        'territorio_id'        => $territorioId,
                        'nombre'               => $nombrePuesto,
                        'codigo_registraduria' => $codigo,
                        'direccion'            => $direccion,
                        'comuna'               => $comuna,
                        'total_mesas'          => $totalMesas,
                        'mujeres'              => $mujeres,
                        'hombres'              => $hombres,
                        'censo_total'          => $totalCenso,
                        'latitud'              => $latitud,
                        'longitud'             => $longitud,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];

                    if (count($puestosAInsertar) >= 100) {
                        DB::table('puestos_votacion')->insert($puestosAInsertar);
                        $puestosAInsertar = [];
                    }
                }
            }
        }

        if (!empty($puestosAInsertar)) {
            DB::table('puestos_votacion')->insert($puestosAInsertar);
        }

        $total = DB::table('puestos_votacion')->count();
        if ($this->command) {
            $this->command->info("✓ Sincronizados con éxito {$total} puestos de votación en todos los 27 municipios de Caldas.");
        }
    }
}
