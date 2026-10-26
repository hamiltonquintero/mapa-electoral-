<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exportaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('tipo', ['datos_personales', 'agregado', 'reporte'])->default('agregado');
            $table->string('motivo');
            $table->json('filtros')->nullable();
            $table->integer('total_registros')->default(0);
            $table->string('archivo_ruta')->nullable();
            $table->enum('estado', ['solicitado', 'procesando', 'completado', 'error'])->default('solicitado');
            $table->text('error_mensaje')->nullable();
            $table->timestamp('descargado_en')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('tipo');
            $table->index('estado');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exportaciones');
    }
};
