<?php

namespace App\Console\Commands;

use App\Models\Campana;
use App\Models\PuestoVotacion;
use App\Models\Simpatizante;
use App\Models\Territorio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerarSimpatizantesCaldas extends Command
{
    protected $signature = 'campana:generar-simpatizantes 
                            {--campana_id=1 : ID de la campaña}
                            {--limpiar : Eliminar simpatizantes anteriores antes de generar}';

    protected $description = 'Genera simpatizantes realistas distribuidos en los 27 municipios y puestos de votación de Caldas';

    public function handle()
    {
        $campanaId = (int) $this->option('campana_id');
        $campana = Campana::find($campanaId);

        if (!$campana) {
            $this->error("No se encontró la campaña con ID {$campanaId}");
            return 1;
        }

        $this->info("=== Generando Simpatizantes para: {$campana->nombre} ===");

        if ($this->option('limpiar')) {
            $this->warn("Eliminando simpatizantes anteriores de la campaña {$campanaId}...");
            Simpatizante::where('campana_id', $campanaId)->forceDelete();
            $this->info("Simpatizantes eliminados.");
        }

        // Nombres típicos colombianos
        $nombresHombres = [
            'Carlos Mario', 'Juan David', 'Andrés Felipe', 'Alejandro', 'Mateo',
            'Santiago', 'Daniel', 'Gabriel', 'Julián', 'Sebastián', 'Nicolás',
            'Diego Fernando', 'Esteban', 'Camilo', 'Mauricio', 'Jorge Iván',
            'Luis Eduardo', 'Hernán Darío', 'José Manuel', 'Álvaro', 'Javier',
            'Guillermo', 'Héctor Fabio', 'Óscar Iván', 'Wilson', 'Rodrigo'
        ];

        $nombresMujeres = [
            'María Camila', 'Valentina', 'Sofía', 'Isabella', 'Daniela',
            'Mariana', 'Lucía', 'Catalina', 'Paula Andrea', 'Natalia',
            'Laura Marcela', 'Ana María', 'Diana Carolina', 'Lina María',
            'Claudia Patricia', 'Gloria Inés', 'Adriana', 'Sandra Milena',
            'Luz Mery', 'Mónica', 'Beatriz Elena', 'Martha Lucía', 'Carmen Rosa',
            'Esperanza', 'Angélica', 'Juliana', 'Manuela'
        ];

        // Apellidos típicos de Caldas y la región cafetera
        $apellidos = [
            'Gómez', 'Jaramillo', 'Henao', 'Restrepo', 'Cardona', 'Osorio',
            'Giraldo', 'Morales', 'Patiño', 'Duque', 'Castaño', 'Ramírez',
            'López', 'Zuluaga', 'Londoño', 'Echeverry', 'Cifuentes', 'Alzate',
            'Gutiérrez', 'Gallego', 'Buitrago', 'Valencia', 'García', 'Mejía',
            'Montoya', 'Herrera', 'Bedoya', 'Quintero', 'Ospina', 'Agudelo',
            'Saldarriaga', 'Aristizábal', 'Tabares', 'Serna', 'Blandón'
        ];

        $prefijosCel = ['310', '311', '312', '313', '314', '320', '321', '300', '301', '315', '316', '317', '318'];

        // Distribución ponderada por los 27 municipios
        $pesosMunicipios = [
            'Manizales'    => 450,
            'La Dorada'    => 180,
            'Chinchiná'    => 140,
            'Villamaría'   => 130,
            'Riosucio'     => 130,
            'Anserma'      => 120,
            'Pensilvania'  => 110,
            'Samaná'       => 110,
            'Salamina'     => 100,
            'Aguadas'      => 95,
            'Supía'        => 90,
            'Neira'        => 85,
            'Viterbo'      => 80,
            'Marquetalia'  => 80,
            'Manzanares'   => 80,
            'Marmato'      => 75,
            'Pácora'       => 70,
            'Aranzazu'     => 65,
            'Belalcázar'   => 65,
            'Filadelfia'   => 60,
            'Victoria'     => 55,
            'Norcasia'     => 50,
            'Palestina'    => 50,
            'Risaralda'    => 50,
            'San José'     => 45,
            'La Merced'    => 45,
            'Marulanda'    => 40,
        ];

        $todosMunicipios = Territorio::where('tipo', 'municipio')->get();
        $totalGeneral = 0;
        $resumenGenerados = [];

        $cedulaBase = 1053700000 + mt_rand(1000, 90000);

        foreach ($todosMunicipios as $muni) {
            $nombreMuni = $muni->nombre;
            $metaCount = $pesosMunicipios[$nombreMuni] ?? 60;

            // Obtener todos los puestos del municipio
            $puestos = PuestoVotacion::where('territorio_id', $muni->id)->get();
            $hijosTerritorio = Territorio::where('padre_id', $muni->id)->get();

            $puestosCount = $puestos->count();
            if ($puestosCount === 0) {
                $this->warn("Municipio {$nombreMuni} no tiene puestos de votación registrados. Omitiendo...");
                continue;
            }

            $generadosMuni = 0;
            $bar = $this->output->createProgressBar($metaCount);
            $bar->setMessage("Generando en {$nombreMuni}...");
            $bar->start();

            // Insertar en bloques de 25 para velocidad
            for ($i = 0; $i < $metaCount; $i++) {
                $esHombre = (mt_rand(0, 100) < 50);
                $primerNombre = $esHombre ? $nombresHombres[array_rand($nombresHombres)] : $nombresMujeres[array_rand($nombresMujeres)];
                $primerApellido = $apellidos[array_rand($apellidos)];
                $segundoApellido = $apellidos[array_rand($apellidos)];

                // Cédula única
                $cedulaBase += mt_rand(1, 15);
                $cedula = (string) $cedulaBase;

                // Teléfono realista
                $prefijo = $prefijosCel[array_rand($prefijosCel)];
                $telefono = $prefijo . str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);

                // Dirección realista
                $tipoVia = ['Calle', 'Carrera', 'Avenida', 'Diagonal', 'Transversal', 'Vereda'][mt_rand(0, 5)];
                $direccion = "{$tipoVia} " . mt_rand(1, 75) . " # " . mt_rand(1, 60) . "-" . mt_rand(1, 80);

                // Asignar a un puesto de este municipio
                $puesto = $puestos->random();
                $totalMesas = max(1, (int) ($puesto->total_mesas ?? 5));
                $mesa = mt_rand(1, $totalMesas);

                // Asignar territorio: si el puesto tiene comuna o si hay hijos (vereda/corregimiento)
                $territorioId = $muni->id;
                if ($hijosTerritorio->isNotEmpty() && mt_rand(0, 100) < 65) {
                    $territorioId = $hijosTerritorio->random()->id;
                }

                // Estado realista (75% validado, 15% borrador, 10% pendiente_evidencia)
                $rndEstado = mt_rand(1, 100);
                $estado = 'validado';
                if ($rndEstado <= 15) {
                    $estado = 'borrador';
                } elseif ($rndEstado <= 25) {
                    $estado = 'pendiente_evidencia';
                }

                // Día D: 35% de los validados ya han votado
                $haVotado = false;
                $horaVoto = null;
                if ($estado === 'validado' && mt_rand(1, 100) <= 35) {
                    $haVotado = true;
                    $horaRandom = sprintf('2026-10-26 %02d:%02d:00', mt_rand(8, 15), mt_rand(0, 59));
                    $horaVoto = $horaRandom;
                }

                $simpatizante = new Simpatizante();
                $simpatizante->campana_id = $campanaId;
                $simpatizante->nombres = $primerNombre;
                $simpatizante->apellidos = "{$primerApellido} {$segundoApellido}";
                $simpatizante->cedula = $cedula;
                $simpatizante->telefono = $telefono;
                $simpatizante->correo = Str::slug("{$primerNombre}.{$primerApellido}") . mt_rand(10, 99) . '@gmail.com';
                $simpatizante->direccion = $direccion;
                $simpatizante->territorio_id = $territorioId;
                $simpatizante->puesto_id = $puesto->id;
                $simpatizante->mesa = $mesa;
                $simpatizante->estado = $estado;
                $simpatizante->ha_votado = $haVotado;
                $simpatizante->hora_voto = $horaVoto;
                $simpatizante->creado_por = 1;
                $simpatizante->save();

                $generadosMuni++;
                $totalGeneral++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $resumenGenerados[] = [
                'Municipio' => $nombreMuni,
                'Puestos'   => $puestosCount,
                'Generados' => $generadosMuni,
            ];
        }

        $this->newLine();
        $this->table(['Municipio', 'Puestos Votación', 'Simpatizantes Generados'], $resumenGenerados);
        $this->info("✅ Proceso completado exitosamente: ¡Se generaron {$totalGeneral} simpatizantes en los 27 municipios de Caldas!");

        return 0;
    }
}
