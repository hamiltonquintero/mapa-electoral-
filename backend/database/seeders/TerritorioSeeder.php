<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TerritorioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Departamento de Caldas
        $caldas = DB::table('territorios')->insertGetId([
            'padre_id' => null,
            'tipo' => 'departamento',
            'nombre' => 'Caldas',
            'codigo_divipola' => '17',
            'centroide_lat' => 5.0689,
            'centroide_lng' => -75.5174,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Municipios principales de Caldas
        $municipios = [
            ['nombre' => 'Manizales', 'codigo' => '17001', 'lat' => 5.0703, 'lng' => -75.5138],
            ['nombre' => 'Villamaría', 'codigo' => '17873', 'lat' => 5.0419, 'lng' => -75.5114],
            ['nombre' => 'Chinchiná', 'codigo' => '17174', 'lat' => 4.9825, 'lng' => -75.6036],
            ['nombre' => 'Palestina', 'codigo' => '17524', 'lat' => 5.0508, 'lng' => -75.4244],
            ['nombre' => 'Neira', 'codigo' => '17486', 'lat' => 5.1675, 'lng' => -75.5194],
            ['nombre' => 'La Dorada', 'codigo' => '17380', 'lat' => 5.4508, 'lng' => -74.6656],
            ['nombre' => 'Aguadas', 'codigo' => '17013', 'lat' => 5.6114, 'lng' => -75.4539],
            ['nombre' => 'Anserma', 'codigo' => '17042', 'lat' => 5.2394, 'lng' => -75.7844],
            ['nombre' => 'Aranzazu', 'codigo' => '17050', 'lat' => 5.2642, 'lng' => -75.4867],
            ['nombre' => 'Belalcázar', 'codigo' => '17088', 'lat' => 5.1983, 'lng' => -75.9042],
            ['nombre' => 'Filadelfia', 'codigo' => '17272', 'lat' => 5.3083, 'lng' => -75.5711],
            ['nombre' => 'Manzanares', 'codigo' => '17433', 'lat' => 5.2544, 'lng' => -75.1539],
            ['nombre' => 'Marmato', 'codigo' => '17442', 'lat' => 5.4761, 'lng' => -75.6711],
            ['nombre' => 'Marquetalia', 'codigo' => '17444', 'lat' => 5.2989, 'lng' => -75.0397],
            ['nombre' => 'Marulanda', 'codigo' => '17446', 'lat' => 5.2786, 'lng' => -75.2281],
            ['nombre' => 'Pácora', 'codigo' => '17513', 'lat' => 5.5244, 'lng' => -75.4661],
            ['nombre' => 'Pensilvania', 'codigo' => '17541', 'lat' => 5.3989, 'lng' => -75.1619],
            ['nombre' => 'Riosucio', 'codigo' => '17614', 'lat' => 5.4211, 'lng' => -75.7036],
            ['nombre' => 'Risaralda', 'codigo' => '17616', 'lat' => 5.1964, 'lng' => -75.7194],
            ['nombre' => 'Salamina', 'codigo' => '17653', 'lat' => 5.4069, 'lng' => -75.4872],
            ['nombre' => 'Samaná', 'codigo' => '17662', 'lat' => 5.4389, 'lng' => -75.0097],
            ['nombre' => 'San José', 'codigo' => '17665', 'lat' => 5.0956, 'lng' => -75.7631],
            ['nombre' => 'Supía', 'codigo' => '17777', 'lat' => 5.4558, 'lng' => -75.6481],
            ['nombre' => 'Victoria', 'codigo' => '17867', 'lat' => 5.3244, 'lng' => -74.9308],
            ['nombre' => 'Viterbo', 'codigo' => '17877', 'lat' => 5.0697, 'lng' => -75.8819],
        ];

        $municipiosIds = [];
        foreach ($municipios as $mun) {
            $municipiosIds[$mun['nombre']] = DB::table('territorios')->insertGetId([
                'padre_id' => $caldas,
                'tipo' => 'municipio',
                'nombre' => $mun['nombre'],
                'codigo_divipola' => $mun['codigo'],
                'centroide_lat' => $mun['lat'],
                'centroide_lng' => $mun['lng'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Comunas/Zonas de Manizales
        $zonasManizales = [
            ['nombre' => 'Comuna Atardeceres', 'lat' => 5.0450, 'lng' => -75.4850],
            ['nombre' => 'Comuna Cumanday', 'lat' => 5.0525, 'lng' => -75.5100],
            ['nombre' => 'Comuna Estación', 'lat' => 5.0685, 'lng' => -75.5100],
            ['nombre' => 'Comuna Ciudadela del Norte', 'lat' => 5.0835, 'lng' => -75.4980],
            ['nombre' => 'Comuna Ecoturistico Cerro de Oro', 'lat' => 5.0580, 'lng' => -75.5250],
            ['nombre' => 'Comuna La Fuente', 'lat' => 5.0615, 'lng' => -75.5065],
            ['nombre' => 'Comuna La Macarena', 'lat' => 5.0755, 'lng' => -75.5145],
            ['nombre' => 'Comuna Palogrande', 'lat' => 5.0680, 'lng' => -75.4950],
            ['nombre' => 'Comuna San José', 'lat' => 5.0730, 'lng' => -75.5195],
            ['nombre' => 'Comuna Universitaria', 'lat' => 5.0590, 'lng' => -75.4880],
            ['nombre' => 'Comuna Tesorito', 'lat' => 5.0890, 'lng' => -75.5050],
        ];

        $comunasManizalesIds = [];
        foreach ($zonasManizales as $zona) {
            $comunasManizalesIds[$zona['nombre']] = DB::table('territorios')->insertGetId([
                'padre_id' => $municipiosIds['Manizales'],
                'tipo' => 'barrio',
                'nombre' => $zona['nombre'],
                'codigo_divipola' => null,
                'centroide_lat' => $zona['lat'],
                'centroide_lng' => $zona['lng'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Barrios de la Comuna Palogrande (ejemplo detallado)
        $barriosPalogrande = [
            ['nombre' => 'Palogrande', 'lat' => 5.0685, 'lng' => -75.4945],
            ['nombre' => 'Palermo', 'lat' => 5.0655, 'lng' => -75.4920],
            ['nombre' => 'Los Cámbulos', 'lat' => 5.0710, 'lng' => -75.4965],
            ['nombre' => 'Versalles', 'lat' => 5.0640, 'lng' => -75.4980],
            ['nombre' => 'Fátima', 'lat' => 5.0700, 'lng' => -75.4990],
        ];

        foreach ($barriosPalogrande as $barrio) {
            DB::table('territorios')->insert([
                'padre_id' => $comunasManizalesIds['Comuna Palogrande'],
                'tipo' => 'barrio',
                'nombre' => $barrio['nombre'],
                'codigo_divipola' => null,
                'centroide_lat' => $barrio['lat'],
                'centroide_lng' => $barrio['lng'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Zonas de Villamaría
        $zonasVillamaria = [
            ['nombre' => 'Zona Centro', 'lat' => 5.0419, 'lng' => -75.5114],
            ['nombre' => 'Zona Norte', 'lat' => 5.0480, 'lng' => -75.5090],
            ['nombre' => 'Zona Sur', 'lat' => 5.0360, 'lng' => -75.5130],
            ['nombre' => 'Gallinazo', 'lat' => 5.0280, 'lng' => -75.5200],
        ];

        foreach ($zonasVillamaria as $zona) {
            DB::table('territorios')->insert([
                'padre_id' => $municipiosIds['Villamaría'],
                'tipo' => 'barrio',
                'nombre' => $zona['nombre'],
                'codigo_divipola' => null,
                'centroide_lat' => $zona['lat'],
                'centroide_lng' => $zona['lng'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 6. Zonas de Chinchiná
        $zonasChinchina = [
            ['nombre' => 'Zona Centro', 'lat' => 4.9825, 'lng' => -75.6036],
            ['nombre' => 'Zona Norte', 'lat' => 4.9880, 'lng' => -75.6010],
            ['nombre' => 'Zona Sur', 'lat' => 4.9770, 'lng' => -75.6060],
            ['nombre' => 'Santágueda', 'lat' => 4.9750, 'lng' => -75.6150],
        ];

        foreach ($zonasChinchina as $zona) {
            DB::table('territorios')->insert([
                'padre_id' => $municipiosIds['Chinchiná'],
                'tipo' => 'barrio',
                'nombre' => $zona['nombre'],
                'codigo_divipola' => null,
                'centroide_lat' => $zona['lat'],
                'centroide_lng' => $zona['lng'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✓ Territorios de Caldas creados:');
        $this->command->info('  → 1 Departamento (Caldas)');
        $this->command->info('  → ' . count($municipios) . ' Municipios');
        $this->command->info('  → 11 Comunas en Manizales');
        $this->command->info('  → 5 Barrios en Comuna Palogrande');
        $this->command->info('  → 4 Zonas en Villamaría');
        $this->command->info('  → 4 Zonas en Chinchiná');
    }
}
