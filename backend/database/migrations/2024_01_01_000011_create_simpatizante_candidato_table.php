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
        Schema::create('simpatizante_candidato', function (Blueprint $table) {
            $table->id();
            $table->uuid('simpatizante_id');
            $table->foreignId('candidato_id')->constrained('candidatos')->onDelete('cascade');
            $table->timestamps();

            $table->foreign('simpatizante_id')
                ->references('id')
                ->on('simpatizantes')
                ->onDelete('cascade');

            $table->unique(['simpatizante_id', 'candidato_id']);
            $table->index('candidato_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simpatizante_candidato');
    }
};
