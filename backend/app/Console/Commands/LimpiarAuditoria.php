<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LimpiarAuditoria extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auditoria:limpiar 
                            {--dias=730 : Número de días de antigüedad para eliminar (mínimo 365)}
                            {--auto : Ejecutar sin confirmación (para cron)}
                            {--dry-run : Simular sin eliminar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar eventos de auditoría antiguos según política de retención';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $auto = $this->option('auto');
        $dryRun = $this->option('dry-run');

        // Validar días mínimos
        if ($dias < 365) {
            $this->error('❌ Error: El mínimo de días de antigüedad es 365 (1 año)');
            $this->info('   Esto garantiza cumplimiento con normativas de retención de auditoría.');
            return Command::FAILURE;
        }

        $fechaLimite = Carbon::now()->subDays($dias);

        $this->info("🔍 Analizando eventos de auditoría...");
        $this->newLine();

        // Contar registros a eliminar
        $totalEliminar = Auditoria::where('creado_en', '<', $fechaLimite)->count();

        if ($totalEliminar === 0) {
            $this->info('✓ No hay eventos de auditoría antiguos que eliminar.');
            return Command::SUCCESS;
        }

        // Mostrar información
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de eventos en el sistema', number_format(Auditoria::count())],
                ['Eventos anteriores a ' . $fechaLimite->format('Y-m-d'), number_format($totalEliminar)],
                ['Porcentaje a eliminar', round(($totalEliminar / Auditoria::count()) * 100, 2) . '%'],
            ]
        );

        $this->newLine();

        // Confirmar acción
        if (!$auto && !$dryRun) {
            $confirmacion = $this->confirm(
                "¿Desea eliminar {$totalEliminar} eventos de auditoría anteriores a {$fechaLimite->format('Y-m-d')}?",
                false
            );

            if (!$confirmacion) {
                $this->warn('⚠️  Operación cancelada por el usuario.');
                return Command::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->info("🔄 Modo DRY-RUN: Se eliminarían {$totalEliminar} registros (no se ejecuta realmente)");
            return Command::SUCCESS;
        }

        // Registrar la limpieza ANTES de eliminar
        $this->info('📝 Registrando operación de limpieza...');
        
        Auditoria::create([
            'user_id' => null,
            'rol' => 'Sistema',
            'accion' => 'limpieza_auditoria',
            'entidad' => 'auditoria',
            'entidad_id' => null,
            'ip' => null,
            'user_agent' => 'Artisan CLI',
            'detalle' => [
                'dias_antiguedad' => $dias,
                'fecha_limite' => $fechaLimite->toDateTimeString(),
                'registros_eliminados' => $totalEliminar,
                'ejecutado_por' => 'comando_artisan',
                'opcion_auto' => $auto,
            ],
            'creado_en' => now(),
        ]);

        // Eliminar registros en bloques para evitar bloqueo de tabla
        $this->info('🗑️  Eliminando eventos antiguos...');
        
        $bloqueSize = 10000;
        $eliminados = 0;
        $progressBar = $this->output->createProgressBar($totalEliminar);

        while (true) {
            $deleted = Auditoria::where('creado_en', '<', $fechaLimite)
                ->limit($bloqueSize)
                ->delete();

            if ($deleted === 0) {
                break;
            }

            $eliminados += $deleted;
            $progressBar->advance($deleted);

            // Pequeña pausa para no saturar la DB
            usleep(100000); // 0.1 segundos
        }

        $progressBar->finish();
        $this->newLine(2);

        // Optimizar tabla después de eliminar muchos registros
        $this->info('⚙️  Optimizando tabla de auditoría...');
        \DB::statement('VACUUM ANALYZE auditoria');

        // Resumen final
        $this->info('✅ Limpieza completada exitosamente');
        $this->newLine();
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Registros eliminados', number_format($eliminados)],
                ['Fecha límite', $fechaLimite->format('Y-m-d H:i:s')],
                ['Eventos restantes', number_format(Auditoria::count())],
            ]
        );

        return Command::SUCCESS;
    }
}
