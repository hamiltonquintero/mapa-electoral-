<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('simpatizantes', 'ha_votado')) {
            Schema::table('simpatizantes', function (Blueprint $table) {
                $table->boolean('ha_votado')->default(false)->after('estado');
                $table->timestamp('hora_voto')->nullable()->after('ha_votado');
                $table->index('ha_votado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('simpatizantes', 'ha_votado')) {
            Schema::table('simpatizantes', function (Blueprint $table) {
                $table->dropIndex(['ha_votado']);
                $table->dropColumn(['ha_votado', 'hora_voto']);
            });
        }
    }
};
