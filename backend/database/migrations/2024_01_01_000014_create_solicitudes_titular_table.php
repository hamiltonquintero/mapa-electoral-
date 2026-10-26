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
        Schema::create('solicitudes_titular', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['consulta', 'rectificacion', 'supresion']);
            $table->uuid('simpatizante_id')->nullable();
            $table->text('cedula_hash')->nullable(); // Para identificar si ya no existe el registro
            $table->enum('estado', ['recibida', 'en_revision', 'resuelta', 'rechazada'])->default('recibida');
            $table->text('descripcion')->nullable();
            $table->date('fecha_recepcion');
            $table->date('fecha_limite'); // 15 días hábiles según ley
            $table->timestamp('resuelta_en')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->foreign('simpatizante_id')
                ->references('id')
                ->on('simpatizantes')
                ->onDelete('set null');

            $table->index('tipo');
            $table->index('estado');
            $table->index('fecha_limite');
            $table->index('responsable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_titular');
    }
};
