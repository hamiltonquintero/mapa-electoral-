<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * MySQL no tiene MATERIALIZED VIEW.
 * Se reemplazan por tablas normales que se refrescan vía scheduler
 * (misma lógica, diferente almacenamiento).
 * 
 * En producción con PostgreSQL se usan vistas materializadas reales.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── mv_conteo_territorio ──────────────────────────────────
        Schema::create('mv_conteo_territorio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campana_id');
            $table->unsignedBigInteger('candidato_id');
            $table->unsignedBigInteger('territorio_id');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('validados')->default(0);
            $table->unsignedInteger('pendientes')->default(0);
            $table->timestamp('ultima_actualizacion')->nullable();

            $table->unique(['campana_id', 'candidato_id', 'territorio_id'], 'mv_conteo_territorio_unique');
            $table->index('campana_id',    'mv_conteo_territorio_campana_idx');
            $table->index('territorio_id', 'mv_conteo_territorio_territorio_idx');
        });

        // ── mv_conteo_puesto_mesa ─────────────────────────────────
        Schema::create('mv_conteo_puesto_mesa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campana_id');
            $table->unsignedBigInteger('candidato_id');
            $table->unsignedBigInteger('puesto_id')->nullable();
            $table->unsignedSmallInteger('mesa')->nullable();
            $table->unsignedBigInteger('territorio_id')->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('validados')->default(0);
            $table->timestamp('ultima_actualizacion')->nullable();

            $table->index('campana_id', 'mv_conteo_puesto_mesa_campana_idx');
            $table->index('puesto_id',  'mv_conteo_puesto_mesa_puesto_idx');
        });

        // ── mv_estadisticas_generales ─────────────────────────────
        Schema::create('mv_estadisticas_generales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campana_id');
            $table->unsignedBigInteger('candidato_id');
            $table->unsignedInteger('total_simpatizantes')->default(0);
            $table->unsignedInteger('total_territorios')->default(0);
            $table->unsignedInteger('total_puestos')->default(0);
            $table->unsignedInteger('total_validados')->default(0);
            $table->unsignedInteger('total_pendientes')->default(0);
            $table->unsignedInteger('sin_puesto')->default(0);
            $table->unsignedInteger('sin_mesa')->default(0);
            $table->unsignedInteger('sin_formulario')->default(0);
            $table->timestamp('ultima_captura')->nullable();
            $table->timestamp('ultima_actualizacion')->nullable();

            $table->unique(['campana_id', 'candidato_id'], 'mv_estadisticas_generales_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mv_estadisticas_generales');
        Schema::dropIfExists('mv_conteo_puesto_mesa');
        Schema::dropIfExists('mv_conteo_territorio');
    }
};
