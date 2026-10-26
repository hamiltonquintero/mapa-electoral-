<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MySQL: se crean stored procedures y triggers equivalentes a las
 * funciones PL/pgSQL de PostgreSQL.
 *
 * En producción (PostgreSQL+PostGIS) se usan las funciones originales.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // ── Trigger de historial de cambios en simpatizantes ──────
        // MySQL requiere un trigger separado (no puede devolver tipo TRIGGER como función)
        DB::unprepared("
            CREATE TRIGGER trigger_registrar_cambio_simpatizante
            AFTER UPDATE ON simpatizantes
            FOR EACH ROW
            BEGIN
                IF OLD.nombres != NEW.nombres THEN
                    INSERT INTO historial_cambios (simpatizante_id, user_id, campo, valor_anterior, valor_nuevo, created_at, updated_at)
                    VALUES (NEW.id, NEW.actualizado_por, 'nombres', OLD.nombres, NEW.nombres, NOW(), NOW());
                END IF;

                IF OLD.apellidos != NEW.apellidos THEN
                    INSERT INTO historial_cambios (simpatizante_id, user_id, campo, valor_anterior, valor_nuevo, created_at, updated_at)
                    VALUES (NEW.id, NEW.actualizado_por, 'apellidos', OLD.apellidos, NEW.apellidos, NOW(), NOW());
                END IF;

                IF OLD.territorio_id != NEW.territorio_id THEN
                    INSERT INTO historial_cambios (simpatizante_id, user_id, campo, valor_anterior, valor_nuevo, created_at, updated_at)
                    VALUES (NEW.id, NEW.actualizado_por, 'territorio_id', CAST(OLD.territorio_id AS CHAR), CAST(NEW.territorio_id AS CHAR), NOW(), NOW());
                END IF;

                IF COALESCE(OLD.puesto_id, 0) != COALESCE(NEW.puesto_id, 0) THEN
                    INSERT INTO historial_cambios (simpatizante_id, user_id, campo, valor_anterior, valor_nuevo, created_at, updated_at)
                    VALUES (NEW.id, NEW.actualizado_por, 'puesto_id', COALESCE(CAST(OLD.puesto_id AS CHAR), 'null'), COALESCE(CAST(NEW.puesto_id AS CHAR), 'null'), NOW(), NOW());
                END IF;

                IF OLD.estado != NEW.estado THEN
                    INSERT INTO historial_cambios (simpatizante_id, user_id, campo, valor_anterior, valor_nuevo, created_at, updated_at)
                    VALUES (NEW.id, NEW.actualizado_por, 'estado', OLD.estado, NEW.estado, NOW(), NOW());
                END IF;
            END
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS trigger_registrar_cambio_simpatizante');
    }
};
