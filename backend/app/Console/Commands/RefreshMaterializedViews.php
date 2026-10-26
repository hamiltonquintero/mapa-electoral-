<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshMaterializedViews extends Command
{
    protected $signature = 'mapa:refresh-views
                            {--view= : Vista específica a refrescar}
                            {--concurrent : Usar CONCURRENTLY en PostgreSQL}';

    protected $description = 'Refrescar vistas/tablas de conteos del mapa electoral';

    private const VIEWS = [
        'mv_conteo_territorio',
        'mv_conteo_puesto_mesa',
        'mv_estadisticas_generales',
    ];

    public function handle(): int
    {
        $view  = $this->option('view');
        $start = microtime(true);
        $driver = config('database.default');

        $this->info("Refrescando conteos ({$driver})...");
        $this->newLine();

        try {
            $views = $view ? [$view] : self::VIEWS;

            foreach ($views as $v) {
                if (!in_array($v, self::VIEWS)) {
                    $this->error("Vista '{$v}' no reconocida.");
                    return 1;
                }
                $t = microtime(true);
                $this->refreshView($v, $driver);
                $this->info(sprintf('✓ %-35s (%.2fs)', $v, microtime(true) - $t));
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info(sprintf('Listo en %.2fs', microtime(true) - $start));
        $this->showStats();

        return 0;
    }

    private function refreshView(string $view, string $driver): void
    {
        if ($driver === 'pgsql') {
            $mode = $this->option('concurrent') ? 'CONCURRENTLY' : '';
            DB::statement("REFRESH MATERIALIZED VIEW {$mode} {$view}");
            return;
        }

        // MySQL — recalcular la tabla manualmente
        match ($view) {
            'mv_conteo_territorio'    => $this->refreshConteoTerritorio(),
            'mv_conteo_puesto_mesa'   => $this->refreshConteoPuestoMesa(),
            'mv_estadisticas_generales' => $this->refreshEstadisticas(),
        };
    }

    private function refreshConteoTerritorio(): void
    {
        DB::table('mv_conteo_territorio')->truncate();

        DB::statement("
            INSERT INTO mv_conteo_territorio
                (campana_id, candidato_id, territorio_id, total, validados, pendientes, ultima_actualizacion)
            SELECT
                s.campana_id,
                sc.candidato_id,
                s.territorio_id,
                COUNT(*)                                                           AS total,
                COUNT(CASE WHEN s.estado = 'validado'            THEN 1 END)      AS validados,
                COUNT(CASE WHEN s.estado = 'pendiente_evidencia' THEN 1 END)      AS pendientes,
                NOW()
            FROM simpatizantes s
            INNER JOIN simpatizante_candidato sc ON s.id = sc.simpatizante_id
            WHERE s.deleted_at IS NULL
              AND s.estado != 'retirado'
            GROUP BY s.campana_id, sc.candidato_id, s.territorio_id
        ");
    }

    private function refreshConteoPuestoMesa(): void
    {
        DB::table('mv_conteo_puesto_mesa')->truncate();

        DB::statement("
            INSERT INTO mv_conteo_puesto_mesa
                (campana_id, candidato_id, puesto_id, mesa, territorio_id, total, validados, ultima_actualizacion)
            SELECT
                s.campana_id,
                sc.candidato_id,
                s.puesto_id,
                s.mesa,
                s.territorio_id,
                COUNT(*)                                                      AS total,
                COUNT(CASE WHEN s.estado = 'validado' THEN 1 END)           AS validados,
                NOW()
            FROM simpatizantes s
            INNER JOIN simpatizante_candidato sc ON s.id = sc.simpatizante_id
            WHERE s.deleted_at IS NULL
              AND s.estado != 'retirado'
              AND s.puesto_id IS NOT NULL
            GROUP BY s.campana_id, sc.candidato_id, s.puesto_id, s.mesa, s.territorio_id
        ");
    }

    private function refreshEstadisticas(): void
    {
        DB::table('mv_estadisticas_generales')->truncate();

        DB::statement("
            INSERT INTO mv_estadisticas_generales
                (campana_id, candidato_id, total_simpatizantes, total_territorios, total_puestos,
                 total_validados, total_pendientes, sin_puesto, sin_mesa, sin_formulario,
                 ultima_captura, ultima_actualizacion)
            SELECT
                s.campana_id,
                sc.candidato_id,
                COUNT(*)                                                              AS total_simpatizantes,
                COUNT(DISTINCT s.territorio_id)                                      AS total_territorios,
                COUNT(DISTINCT s.puesto_id)                                          AS total_puestos,
                COUNT(CASE WHEN s.estado = 'validado'            THEN 1 END)         AS total_validados,
                COUNT(CASE WHEN s.estado = 'pendiente_evidencia' THEN 1 END)         AS total_pendientes,
                COUNT(CASE WHEN s.puesto_id IS NULL              THEN 1 END)         AS sin_puesto,
                COUNT(CASE WHEN s.mesa IS NULL AND s.puesto_id IS NOT NULL THEN 1 END) AS sin_mesa,
                COUNT(CASE WHEN s.formulario_id IS NULL          THEN 1 END)         AS sin_formulario,
                MAX(s.created_at),
                NOW()
            FROM simpatizantes s
            INNER JOIN simpatizante_candidato sc ON s.id = sc.simpatizante_id
            WHERE s.deleted_at IS NULL
              AND s.estado != 'retirado'
            GROUP BY s.campana_id, sc.candidato_id
        ");
    }

    private function showStats(): void
    {
        $rows = DB::table('mv_conteo_territorio')
                  ->selectRaw('COUNT(*) as filas, COALESCE(SUM(total), 0) as apoyos')
                  ->first();

        $this->line(sprintf(
            '  mv_conteo_territorio: %d filas · %d apoyos',
            $rows?->filas ?? 0,
            $rows?->apoyos ?? 0
        ));
    }
}
