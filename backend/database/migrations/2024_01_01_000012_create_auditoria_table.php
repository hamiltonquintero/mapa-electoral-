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
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('rol')->nullable();
            $table->string('accion'); // login, logout, crear, editar, eliminar, exportar, etc.
            $table->string('entidad')->nullable(); // simpatizante, usuario, territorio, etc.
            $table->string('entidad_id')->nullable(); // ID del registro afectado
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('detalle')->nullable(); // Información adicional SIN datos personales
            $table->timestamp('creado_en')->useCurrent();

            // Índices para búsqueda eficiente
            $table->index('user_id');
            $table->index('accion');
            $table->index('entidad');
            $table->index(['entidad', 'entidad_id']);
            $table->index('creado_en');
            $table->index('ip');
        });

        // Particionado por mes (opcional, para bases grandes)
        // Se puede implementar después si es necesario
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
