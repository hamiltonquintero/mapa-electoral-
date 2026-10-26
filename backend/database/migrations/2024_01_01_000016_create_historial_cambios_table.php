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
        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id();
            $table->uuid('simpatizante_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('campo');
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();
            $table->timestamp('creado_en')->useCurrent();

            $table->foreign('simpatizante_id')
                ->references('id')
                ->on('simpatizantes')
                ->onDelete('cascade');

            $table->index('simpatizante_id');
            $table->index('user_id');
            $table->index('creado_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_cambios');
    }
};
