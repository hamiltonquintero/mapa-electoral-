<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PuestoVotacion;
use App\Models\Territorio;

class SincronizarCaldasPuestos extends Command
{
    protected $signature = 'mapa:sincronizar-censo {--limpiar : Limpiar puestos de votación antes de sincronizar}';
    protected $description = 'Sincroniza los 339 puestos del censo electoral con la tabla de puestos_votacion del sistema';

    public function handle(): int
    {
        $this->info('Iniciando sincronización de puestos del censo electoral...');

        // Mapeo normalizado de los 27 municipios de Caldas a su ID en territorios
        $municipiosMap = [
            'MANIZALES'   => 2,
            'VILLAMARIA'  => 3,
            'CHINCHINA'   => 4,
            'PALESTINA'   => 5,
            'NEIRA'       => 6,
            'LA DORADA'   => 7,
            'AGUADAS'     => 8,
            'ANSERMA'     => 9,
            'ARANZAZU'    => 10,
            'BELALCAZAR'  => 11,
            'FILADELFIA'  => 12,
            'MANZANARES'  => 13,
            'MARMATO'     => 14,
            'MARQUETALIA' => 15,
            'MARULANDA'   => 16,
            'PACORA'      => 17,
            'PENSILVANIA' => 18,
            'RIOSUCIO'    => 19,
            'RISARALDA'   => 20,
            'SALAMINA'    => 21,
            'SAMANA'      => 22,
            'SAN JOSE'    => 23,
            'SUPIA'       => 24,
            'VICTORIA'    => 25,
            'VITERBO'     => 26,
            'LA MERCED'   => 27,
            'NORCASIA'    => 28,
        ];

        // Obtener todos los puestos del censo electoral
        $puestosCenso = DB::table('puestos_censo_electoral')->orderBy('id')->get();
        if ($puestosCenso->isEmpty()) {
            $this->error('No hay datos en la tabla puestos_censo_electoral.');
            return 1;
        }

        $this->info("Puestos a procesar en el censo: {$puestosCenso->count()}");

        $actualizados = 0;
        $creados = 0;

        foreach ($puestosCenso as $censo) {
            $muniClean = strtoupper(trim($censo->municipio));
            $territorioId = $municipiosMap[$muniClean] ?? null;

            if (!$territorioId) {
                $this->warn("Municipio no reconocido: {$censo->municipio} (Puesto: {$censo->puesto})");
                continue;
            }

            // Normalización para búsqueda de coincidencia
            $puestoNombre = trim($censo->puesto);

            // Buscar si ya existe un puesto con el nombre exacto en ese territorio
            $existente = PuestoVotacion::where('territorio_id', $territorioId)
                ->where('nombre', $puestoNombre)
                ->first();

            if ($existente) {
                // Actualizar con datos oficiales y enriquecidos
                $existente->update([
                    'direccion'   => $censo->direccion ?: $existente->direccion,
                    'total_mesas' => (int) $censo->mesas_domingo,
                    'comuna'      => $censo->comuna,
                    'mujeres'     => (int) $censo->mujeres,
                    'hombres'     => (int) $censo->hombres,
                    'censo_total' => (int) $censo->total,
                    'latitud'     => $censo->latitud ?: $existente->latitud,
                    'longitud'    => $censo->longitud ?: $existente->longitud,
                ]);
                $actualizados++;
            } else {
                // Crear nuevo puesto
                PuestoVotacion::create([
                    'territorio_id'        => $territorioId,
                    'nombre'               => $puestoNombre,
                    'direccion'            => $censo->direccion,
                    'total_mesas'          => (int) $censo->mesas_domingo,
                    'comuna'               => $censo->comuna,
                    'mujeres'              => (int) $censo->mujeres,
                    'hombres'              => (int) $censo->hombres,
                    'censo_total'          => (int) $censo->total,
                    'codigo_registraduria' => 'PV' . ($territorioId * 1000 + $censo->id),
                    'latitud'              => $censo->latitud,
                    'longitud'             => $censo->longitud,
                ]);
                $creados++;
            }
        }

        $totalFinal = PuestoVotacion::count();
        $this->info("--------------------------------------------------");
        $this->info("Sincronización completada exitosamente:");
        $this->info("- Puestos actualizados existentes: {$actualizados}");
        $this->info("- Puestos nuevos creados        : {$creados}");
        $this->info("- Total puestos en puestos_votacion: {$totalFinal}");
        $this->info("--------------------------------------------------");

        return 0;
    }
}
