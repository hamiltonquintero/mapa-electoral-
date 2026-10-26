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
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->foreignId('territorio_id')->nullable()->constrained('territorios')->onDelete('set null');
            $table->date('fecha');
            $table->foreignId('creado_por')->constrained('users')->onDelete('cascade');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('creado_por');
            $table->index('fecha');
            $table->index('territorio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
