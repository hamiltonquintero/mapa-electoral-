<?php

namespace App\Console\Commands;

use App\Models\Importacion;
use App\Models\PuestoVotacion;
use Illuminate\Console\Command;

class ReportImportaciones extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'report:importaciones';

    /**
     * The console command description.
     */
    protected $description = 'Muestra un resumen de la última importación y los primeros 5 puestos de votación';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $importacion = Importacion::latest()->first();
        $puestos = PuestoVotacion::orderBy('id')->take(5)->get();

        $this->info('--- Resumen de Importación ---');
        if ($importacion) {
            $this->line('ID: ' . $importacion->id);
            $this->line('Archivo: ' . $importacion->nombre_archivo);
            $this->line('Filas importadas: ' . $importacion->filas_importadas);
            $this->line('Filas exitosas: ' . $importacion->filas_exitosas);
            $this->line('Filas fallidas: ' . $importacion->filas_fallidas);
            $this->line('Estado: ' . $importacion->estado);
        } else {
            $this->error('No se encontró ninguna importación.');
        }

        $this->info('--- Primeros 5 Puestos ---');
        foreach ($puestos as $p) {
            $this->line("{$p->id}: {$p->nombre} ({$p->latitud}, {$p->longitud})");
        }

        return 0;
    }
}
