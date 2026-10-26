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
        Schema::create('simpatizantes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('campana_id')->constrained('campanas')->onDelete('cascade');
            
            // Datos personales (algunos cifrados)
            $table->string('nombres');
            $table->string('apellidos');
            $table->text('cedula_cifrada'); // Cifrado AES-256-GCM
            $table->string('cedula_hash', 64); // HMAC-SHA256 para búsqueda
            $table->text('telefono_cifrado')->nullable();
            $table->text('correo_cifrado')->nullable();
            $table->text('direccion_cifrada')->nullable();
            
            // Ubicación territorial
            $table->foreignId('territorio_id')->constrained('territorios')->onDelete('cascade');
            $table->foreignId('puesto_id')->nullable()->constrained('puestos_votacion')->onDelete('set null');
            $table->integer('mesa')->nullable();
            
            // Referencias
            $table->foreignId('lider_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('formulario_id')->nullable()->constrained('formularios')->onDelete('set null');
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->onDelete('set null');
            
            // Estado y control
            $table->enum('estado', ['borrador', 'validado', 'observado', 'retirado', 'pendiente_evidencia'])->default('borrador');
            $table->text('observaciones')->nullable();
            
            // Auditoría
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique(['campana_id', 'cedula_hash']);
            $table->index('campana_id');
            $table->index('territorio_id');
            $table->index('puesto_id');
            $table->index('estado');
            $table->index('lider_id');
            $table->index('creado_por');
            $table->index(['puesto_id', 'mesa']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simpatizantes');
    }
};
