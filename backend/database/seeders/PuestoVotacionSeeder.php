<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PuestoVotacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener territorios
        $manizales = DB::table('territorios')->where('nombre', 'Manizales')->first();
        $villamaria = DB::table('territorios')->where('nombre', 'Villamaría')->first();
        $chinchina = DB::table('territorios')->where('nombre', 'Chinchiná')->first();
        
        // Obtener veredas/barrios
        $palogrande = DB::table('territorios')->where('nombre', 'Comuna Palogrande')->first();
        $cumanday = DB::table('territorios')->where('nombre', 'Comuna Cumanday')->first();
        $estacion = DB::table('territorios')->where('nombre', 'Comuna Estación')->first();
        $zonaCentroVM = DB::table('territorios')->where('nombre', 'Zona Centro')->where('padre_id', $villamaria->id)->first();
        $zonaCentroCh = DB::table('territorios')->where('nombre', 'Zona Centro')->where('padre_id', $chinchina->id)->first();

        $puestos = [];

        // ========== MANIZALES - PALOGRANDE ==========
        $puestos[] = [
            'territorio_id' => $palogrande->id,
            'nombre' => 'I.E. Pío XII',
            'codigo' => 'PV-MAN-001',
            'direccion' => 'Calle 42 #23-15, Palogrande',
            'total_mesas' => 15,
            'lat' => 5.0685,
            'lng' => -75.4945,
        ];
        
        $puestos[] = [
            'territorio_id' => $palogrande->id,
            'nombre' => 'Colegio Mayor de Nuestra Señora',
            'codigo' => 'PV-MAN-002',
            'direccion' => 'Calle 62 #26-20, Palermo',
            'total_mesas' => 12,
            'lat' => 5.0655,
            'lng' => -75.4920,
        ];

        $puestos[] = [
            'territorio_id' => $palogrande->id,
            'nombre' => 'Colegio de Cristo',
            'codigo' => 'PV-MAN-003',
            'direccion' => 'Carrera 23 #65-50, Palogrande',
            'total_mesas' => 18,
            'lat' => 5.0690,
            'lng' => -75.4950,
        ];

        // ========== MANIZALES - CUMANDAY ==========
        $puestos[] = [
            'territorio_id' => $cumanday->id,
            'nombre' => 'I.E. Gonzalo Mejía Echeverri',
            'codigo' => 'PV-MAN-004',
            'direccion' => 'Calle 28 #34-10, Cumanday',
            'total_mesas' => 20,
            'lat' => 5.0525,
            'lng' => -75.5100,
        ];

        $puestos[] = [
            'territorio_id' => $cumanday->id,
            'nombre' => 'Colegio Santa Inés',
            'codigo' => 'PV-MAN-005',
            'direccion' => 'Carrera 32 #25-40, Cumanday',
            'total_mesas' => 14,
            'lat' => 5.0530,
            'lng' => -75.5105,
        ];

        // ========== MANIZALES - ESTACIÓN ==========
        if ($estacion) {
            $puestos[] = [
                'territorio_id' => $estacion->id,
                'nombre' => 'I.E. La Salle',
                'codigo' => 'PV-MAN-006',
                'direccion' => 'Carrera 22 #50-10, Estación',
                'total_mesas' => 16,
                'lat' => 5.0685,
                'lng' => -75.5100,
            ];
        }

        // ========== VILLAMARÍA ==========
        if ($zonaCentroVM) {
            $puestos[] = [
                'territorio_id' => $zonaCentroVM->id,
                'nombre' => 'I.E. Francisco Miranda',
                'codigo' => 'PV-VIL-001',
                'direccion' => 'Calle 5 #6-20, Centro',
                'total_mesas' => 22,
                'lat' => 5.0419,
                'lng' => -75.5114,
            ];

            $puestos[] = [
                'territorio_id' => $zonaCentroVM->id,
                'nombre' => 'Colegio Nazaret',
                'codigo' => 'PV-VIL-002',
                'direccion' => 'Carrera 4 #7-15, Centro',
                'total_mesas' => 10,
                'lat' => 5.0425,
                'lng' => -75.5120,
            ];
        }

        // ========== CHINCHINÁ ==========
        if ($zonaCentroCh) {
            $puestos[] = [
                'territorio_id' => $zonaCentroCh->id,
                'nombre' => 'I.E. San José',
                'codigo' => 'PV-CHI-001',
                'direccion' => 'Calle 10 #8-30, Centro',
                'total_mesas' => 18,
                'lat' => 4.9825,
                'lng' => -75.6036,
            ];

            $puestos[] = [
                'territorio_id' => $zonaCentroCh->id,
                'nombre' => 'Escuela Normal Superior',
                'codigo' => 'PV-CHI-002',
                'direccion' => 'Carrera 7 #12-05, Centro',
                'total_mesas' => 16,
                'lat' => 4.9830,
                'lng' => -75.6040,
            ];
        }

        // Insertar puestos de votación
        foreach ($puestos as $puesto) {
            DB::table('puestos_votacion')->insert([
                'territorio_id' => $puesto['territorio_id'],
                'nombre' => $puesto['nombre'],
                'codigo_registraduria' => $puesto['codigo'],
                'direccion' => $puesto['direccion'],
                'total_mesas' => $puesto['total_mesas'],
                'latitud' => $puesto['lat'],
                'longitud' => $puesto['lng'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✓ Puestos de votación creados: ' . count($puestos));
        $this->command->info('  → Manizales: 6 puestos');
        $this->command->info('  → Villamaría: 2 puestos');
        $this->command->info('  → Chinchiná: 2 puestos');
    }
}
