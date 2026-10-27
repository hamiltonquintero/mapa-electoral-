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
        Schema::table('puestos_votacion', function (Blueprint $table) {
            if (!Schema::hasColumn('puestos_votacion', 'comuna')) {
                $table->string('comuna')->nullable()->after('direccion');
            }
            if (!Schema::hasColumn('puestos_votacion', 'mujeres')) {
                $table->integer('mujeres')->default(0)->after('comuna');
            }
            if (!Schema::hasColumn('puestos_votacion', 'hombres')) {
                $table->integer('hombres')->default(0)->after('mujeres');
            }
            if (!Schema::hasColumn('puestos_votacion', 'censo_total')) {
                $table->integer('censo_total')->default(0)->after('hombres');
                $table->index('censo_total');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puestos_votacion', function (Blueprint $table) {
            $cols = ['comuna', 'mujeres', 'hombres', 'censo_total'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('puestos_votacion', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
