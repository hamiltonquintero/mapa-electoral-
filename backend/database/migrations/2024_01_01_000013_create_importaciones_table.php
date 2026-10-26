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
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas')->onDelete('cascade');
            $table->string('archivo_original');
            $table->string('archivo_ruta');
            $table->enum('estado', ['pendiente', 'validando', 'validado', 'importando', 'completado', 'error', 'revertido'])->default('pendiente');
            $table->integer('filas_total')->default(0);
            $table->integer('filas_ok')->default(0);
            $table->integer('filas_error')->default(0);
            $table->json('errores')->nullable(); // Detalles de errores por fila
            $table->boolean('reversible')->default(true);
            $table->timestamp('reversible_hasta')->nullable();
            $table->timestamp('revertido_en')->nullable();
            $table->foreignId('revertido_por')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('campana_id');
            $table->index('estado');
            $table->index('creado_por');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
