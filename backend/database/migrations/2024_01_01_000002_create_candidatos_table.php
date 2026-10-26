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
        Schema::create('candidatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas')->onDelete('cascade');
            $table->string('nombre');
            $table->enum('cargo', ['gobernacion', 'alcaldia', 'asamblea']);
            $table->foreignId('territorio_id')->nullable()->constrained('territorios')->onDelete('set null');
            $table->string('color', 7)->default('#3B82F6'); // Color corporativo (hex)
            $table->text('descripcion')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campana_id', 'cargo']);
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidatos');
    }
};
