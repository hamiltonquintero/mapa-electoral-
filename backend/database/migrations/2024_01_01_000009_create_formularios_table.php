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
        Schema::create('formularios', function (Blueprint $table) {
            $table->id();
            $table->string('consecutivo', 50)->unique();
            $table->text('archivo_ruta'); // Cifrado
            $table->string('hash_sha256', 64);
            $table->date('fecha_firma');
            $table->string('tipo_archivo', 10); // jpg, png, pdf
            $table->integer('tamano_bytes');
            $table->foreignId('subido_por')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('consecutivo');
            $table->index('hash_sha256');
            $table->index('subido_por');
            $table->index('fecha_firma');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formularios');
    }
};
