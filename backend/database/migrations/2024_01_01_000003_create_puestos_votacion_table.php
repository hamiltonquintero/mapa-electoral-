<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('puestos_votacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('territorio_id')->constrained('territorios')->onDelete('cascade');
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->integer('total_mesas')->default(0);
            $table->string('codigo_registraduria', 20)->nullable();
            $table->unsignedBigInteger('importacion_id')->nullable();
            $table->timestamps();

            $table->index('territorio_id');
            $table->index('importacion_id');
            $table->index('codigo_registraduria');
        });

        // Columnas de coordenadas — MySQL compatible
        // También se indexa la latitud y longitud para búsquedas rápidas
        // En producción con PostgreSQL+PostGIS se usa geometry(Point,4326)
        Schema::table('puestos_votacion', function (Blueprint $table) {
            $table->decimal('latitud',  10, 7)->nullable();
            $table->decimal('longitud', 11, 7)->nullable();
            $table->index('latitud');
            $table->index('longitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puestos_votacion');
    }
};
