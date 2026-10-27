<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimpatizanteSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar simpatizantes anteriores para evitar duplicados
        DB::table('simpatizantes')->delete();
        $this->command?->info('✓ Simpatizantes anteriores eliminados');

        // Obtener campaña
        $campana = DB::table('campanas')->orderBy('id')->first();
        if (!$campana) {
            $campanaId = DB::table('campanas')->insertGetId([
                'nombre'      => 'Campaña Electoral 2027',
                'descripcion' => 'Campaña principal para elecciones regionales',
                'activa'      => true,
                'fecha_inicio'=> '2027-01-01',
                'fecha_cierre'=> '2027-12-31',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $campana = DB::table('campanas')->find($campanaId);
            $this->command?->info('✓ Campaña creada: Campaña Electoral 2027');
        } else {
            $this->command?->info('✓ Usando campaña: ' . $campana->nombre);
        }

        $superadminId = DB::table('users')->where('email', 'superadmin@mapa.com')->value('id') 
            ?? DB::table('users')->value('id') 
            ?? 1;
        
        // Asociar campaña a todos los usuarios del sistema
        $allUsers = DB::table('users')->get();
        foreach ($allUsers as $u) {
            DB::table('usuario_campana')->updateOrInsert(
                ['user_id' => $u->id, 'campana_id' => $campana->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $puestos = DB::table('puestos_votacion')->get();

        if ($this->command) {
            $this->command->info("✓ Puestos de votación encontrados: {$puestos->count()}");
        }

        $cifrar    = fn($t) => base64_encode($t);
        $hashCed   = fn($c) => hash_hmac('sha256', $c, 'demo_key_' . $campana->id);

        $nombres   = ['Juan','María','Pedro','Ana','Luis','Carmen','Carlos','Diana',
                      'Jorge','Patricia','Andrés','Laura','Fernando','Sandra',
                      'Miguel','Claudia','Ricardo','Mónica','Alberto','Rosa',
                      'Héctor','Isabel','Gustavo','Natalia','Felipe','Alejandra'];
        $apellidos = ['García','Rodríguez','González','Fernández','López','Martínez',
                      'Pérez','Gómez','Sánchez','Ramírez','Torres','Flores',
                      'Rivera','Silva','Reyes','Morales','Jiménez','Herrera',
                      'Castillo','Vargas','Ramos','Díaz','Cruz','Ortega'];

        // Cedula con timestamp para garantizar unicidad absoluta
        $base    = (int)(microtime(true) * 1000) % 10000000;
        $counter = $base * 1000;
        $lote    = [];
        $stats   = [];

        foreach ($puestos as $puesto) {
            $cantidad = rand(2, 6);
            for ($i = 0; $i < $cantidad; $i++) {
                $counter++;
                $cedula = (string)$counter;
                $nom    = $nombres[array_rand($nombres)];
                $ap1    = $apellidos[array_rand($apellidos)];
                $ap2    = $apellidos[array_rand($apellidos)];

                $lote[] = [
                    'id'               => (string) Str::uuid(),
                    'campana_id'       => $campana->id,
                    'nombres'          => $nom,
                    'apellidos'        => "$ap1 $ap2",
                    'cedula_cifrada'   => $cifrar($cedula),
                    'cedula_hash'      => $hashCed($cedula),
                    'telefono_cifrado' => $cifrar('310' . rand(1000000, 9999999)),
                    'correo_cifrado'   => $cifrar(strtolower($nom . $counter) . '@example.com'),
                    'territorio_id'    => $puesto->territorio_id,
                    'puesto_id'        => $puesto->id,
                    'mesa'             => rand(1, max(1, $puesto->total_mesas)),
                    'estado'           => 'validado',
                    'creado_por'       => $superadminId,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];

                // Acumular estadísticas por territorio
                $tid = $puesto->territorio_id;
                $stats[$tid] = ($stats[$tid] ?? 0) + 1;
            }

            // Insertar en lotes de 200 para eficiencia
            if (count($lote) >= 200) {
                DB::table('simpatizantes')->insert($lote);
                $lote = [];
            }
        }

        // Insertar el lote restante
        if (!empty($lote)) {
            DB::table('simpatizantes')->insert($lote);
        }

        $total = DB::table('simpatizantes')->where('campana_id', $campana->id)->count();
        if ($this->command) {
            $this->command->info("✓ Total simpatizantes creados: $total");
        }

        // Resumen por municipio
        $municipios = DB::table('territorios')
            ->where('tipo', 'municipio')
            ->pluck('nombre', 'id');

        // Contar directamente en BD
        $conteoPorMun = DB::table('simpatizantes as s')
            ->join('territorios as t', 's.territorio_id', '=', 't.id')
            ->where('s.campana_id', $campana->id)
            ->selectRaw('t.nombre as municipio, COUNT(*) as total')
            ->groupBy('t.nombre')
            ->orderByDesc('total')
            ->get();

        if ($this->command) {
            foreach ($conteoPorMun as $row) {
                $this->command->info("  → {$row->municipio}: {$row->total} simpatizantes");
            }
        }
    }
}
