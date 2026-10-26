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
        Schema::create('territorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('padre_id')->nullable()->constrained('territorios')->onDelete('cascade');
            $table->enum('tipo', ['departamento', 'municipio', 'corregimiento', 'vereda', 'barrio']);
            $table->string('nombre');
            $table->string('codigo_divipola', 10)->nullable();
            $table->timestamps();

            $table->index('padre_id');
            $table->index('tipo');
            $table->index(['padre_id', 'tipo']);
            $table->unique(['codigo_divipola']);
        });

        // Columnas de geometría — MySQL usa GEOMETRY nativo (sin parámetros PostGIS)
        // En producción con PostgreSQL+PostGIS se usan tipos geometry(MultiPolygon,4326)
        // Para MySQL de desarrollo usamos TEXT para almacenar GeoJSON/WKT
        Schema::table('territorios', function (Blueprint $table) {
            $table->text('geom')->nullable()->comment('GeoJSON del polígono del territorio');
            $table->decimal('centroide_lat', 10, 7)->nullable();
            $table->decimal('centroide_lng', 11, 7)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('territorios');
    }
};
