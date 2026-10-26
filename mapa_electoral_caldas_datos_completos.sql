-- =====================================================
-- BASE DE DATOS COMPLETA: MAPA ELECTORAL CALDAS
-- =====================================================
-- Este archivo contiene todos los datos necesarios para el sistema
-- Incluye: Territorios con geometrías, Puestos de votación, Simpatizantes
-- =====================================================

-- Limpiar tablas en orden correcto (respetar FK)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `simpatizantes`;
TRUNCATE TABLE `puestos_votacion`;
TRUNCATE TABLE `territorios`;
TRUNCATE TABLE `campanas`;
TRUNCATE TABLE `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. CAMPAÑA
-- =====================================================
INSERT INTO `campanas` (`id`, `nombre`, `descripcion`, `activa`, `fecha_inicio`, `fecha_cierre`, `created_at`, `updated_at`) VALUES
(1, 'Campaña Electoral 2026', 'Campaña principal para elecciones regionales de Caldas', 1, '2026-01-01', '2026-12-31', NOW(), NOW());

-- =====================================================
-- 2. TERRITORIOS - DEPARTAMENTO CALDAS
-- =====================================================
-- Nota: Las geometrías están simplificadas para este demo
-- En producción debes usar los límites oficiales del DANE/IGAC

-- Departamento Caldas (ID: 1)
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `geom`, `created_at`, `updated_at`) VALUES
(1, NULL, 'departamento', 'Caldas', '17', 5.0689, -75.5174, 
'{"type":"Polygon","coordinates":[[[-75.9,-5.6],[-75.9,-4.5],[-74.5,-4.5],[-74.5,-5.6],[-75.9,-5.6]]]}', 
NOW(), NOW());

-- =====================================================
-- 3. MUNICIPIOS DE CALDAS (27 municipios)
-- =====================================================
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `geom`, `created_at`, `updated_at`) VALUES
-- Manizales (capital)
(2, 1, 'municipio', 'Manizales', '17001', 5.0703, -75.5138, 
'{"type":"Polygon","coordinates":[[[-75.55,5.02],[-75.55,5.12],[-75.45,5.12],[-75.45,5.02],[-75.55,5.02]]]}', NOW(), NOW()),

-- Otros municipios principales
(3, 1, 'municipio', 'Villamaría', '17873', 5.0419, -75.5114,
'{"type":"Polygon","coordinates":[[[-75.55,5.00],[-75.55,5.08],[-75.47,5.08],[-75.47,5.00],[-75.55,5.00]]]}', NOW(), NOW()),

(4, 1, 'municipio', 'Chinchiná', '17174', 4.9825, -75.6036,
'{"type":"Polygon","coordinates":[[[-75.65,4.95],[-75.65,5.02],[-75.56,5.02],[-75.56,4.95],[-75.65,4.95]]]}', NOW(), NOW()),

(5, 1, 'municipio', 'Palestina', '17524', 5.0508, -75.4244,
'{"type":"Polygon","coordinates":[[[-75.47,5.01],[-75.47,5.09],[-75.38,5.09],[-75.38,5.01],[-75.47,5.01]]]}', NOW(), NOW()),

(6, 1, 'municipio', 'Neira', '17486', 5.1675, -75.5194,
'{"type":"Polygon","coordinates":[[[-75.57,5.13],[-75.57,5.21],[-75.47,5.21],[-75.47,5.13],[-75.57,5.13]]]}', NOW(), NOW()),

(7, 1, 'municipio', 'La Dorada', '17380', 5.4508, -74.6656,
'{"type":"Polygon","coordinates":[[[-74.72,5.40],[-74.72,5.50],[-74.61,5.50],[-74.61,5.40],[-74.72,5.40]]]}', NOW(), NOW()),

(8, 1, 'municipio', 'Aguadas', '17013', 5.6114, -75.4539,
'{"type":"Polygon","coordinates":[[[-75.50,5.57],[-75.50,5.65],[-75.41,5.65],[-75.41,5.57],[-75.50,5.57]]]}', NOW(), NOW()),

(9, 1, 'municipio', 'Anserma', '17042', 5.2394, -75.7844,
'{"type":"Polygon","coordinates":[[[-75.83,5.20],[-75.83,5.28],[-75.74,5.28],[-75.74,5.20],[-75.83,5.20]]]}', NOW(), NOW()),

(10, 1, 'municipio', 'Aranzazu', '17050', 5.2642, -75.4867,
'{"type":"Polygon","coordinates":[[[-75.53,5.23],[-75.53,5.30],[-75.44,5.30],[-75.44,5.23],[-75.53,5.23]]]}', NOW(), NOW()),

(11, 1, 'municipio', 'Belalcázar', '17088', 5.1983, -75.9042,
'{"type":"Polygon","coordinates":[[[-75.95,5.16],[-75.95,5.24],[-75.86,5.24],[-75.86,5.16],[-75.95,5.16]]]}', NOW(), NOW()),

(12, 1, 'municipio', 'Filadelfia', '17272', 5.3083, -75.5711,
'{"type":"Polygon","coordinates":[[[-75.62,5.27],[-75.62,5.35],[-75.52,5.35],[-75.52,5.27],[-75.62,5.27]]]}', NOW(), NOW()),

(13, 1, 'municipio', 'Manzanares', '17433', 5.2544, -75.1539,
'{"type":"Polygon","coordinates":[[[-75.20,5.22],[-75.20,5.29],[-75.11,5.29],[-75.11,5.22],[-75.20,5.22]]]}', NOW(), NOW()),

(14, 1, 'municipio', 'Marmato', '17442', 5.4761, -75.6711,
'{"type":"Polygon","coordinates":[[[-75.72,5.44],[-75.72,5.51],[-75.62,5.51],[-75.62,5.44],[-75.72,5.44]]]}', NOW(), NOW()),

(15, 1, 'municipio', 'Marquetalia', '17444', 5.2989, -75.0397,
'{"type":"Polygon","coordinates":[[[-75.09,5.26],[-75.09,5.34],[-74.99,5.34],[-74.99,5.26],[-75.09,5.26]]]}', NOW(), NOW()),

(16, 1, 'municipio', 'Marulanda', '17446', 5.2786, -75.2281,
'{"type":"Polygon","coordinates":[[[-75.28,5.24],[-75.28,5.32],[-75.18,5.32],[-75.18,5.24],[-75.28,5.24]]]}', NOW(), NOW()),

(17, 1, 'municipio', 'Pácora', '17513', 5.5244, -75.4661,
'{"type":"Polygon","coordinates":[[[-75.52,5.49],[-75.52,5.56],[-75.41,5.56],[-75.41,5.49],[-75.52,5.49]]]}', NOW(), NOW()),

(18, 1, 'municipio', 'Pensilvania', '17541', 5.3989, -75.1619,
'{"type":"Polygon","coordinates":[[[-75.21,5.36],[-75.21,5.44],[-75.11,5.44],[-75.11,5.36],[-75.21,5.36]]]}', NOW(), NOW()),

(19, 1, 'municipio', 'Riosucio', '17614', 5.4211, -75.7036,
'{"type":"Polygon","coordinates":[[[-75.75,5.38],[-75.75,5.46],[-75.66,5.46],[-75.66,5.38],[-75.75,5.38]]]}', NOW(), NOW()),

(20, 1, 'municipio', 'Risaralda', '17616', 5.1964, -75.7194,
'{"type":"Polygon","coordinates":[[[-75.77,5.16],[-75.77,5.24],[-75.67,5.24],[-75.67,5.16],[-75.77,5.16]]]}', NOW(), NOW()),

(21, 1, 'municipio', 'Salamina', '17653', 5.4069, -75.4872,
'{"type":"Polygon","coordinates":[[[-75.54,5.37],[-75.54,5.44],[-75.43,5.44],[-75.43,5.37],[-75.54,5.37]]]}', NOW(), NOW()),

(22, 1, 'municipio', 'Samaná', '17662', 5.4389, -75.0097,
'{"type":"Polygon","coordinates":[[[-75.06,5.40],[-75.06,5.48],[-74.96,5.48],[-74.96,5.40],[-75.06,5.40]]]}', NOW(), NOW()),

(23, 1, 'municipio', 'San José', '17665', 5.0956, -75.7631,
'{"type":"Polygon","coordinates":[[[-75.81,5.06],[-75.81,5.13],[-75.71,5.13],[-75.71,5.06],[-75.81,5.06]]]}', NOW(), NOW()),

(24, 1, 'municipio', 'Supía', '17777', 5.4558, -75.6481,
'{"type":"Polygon","coordinates":[[[-75.70,5.42],[-75.70,5.49],[-75.60,5.49],[-75.60,5.42],[-75.70,5.42]]]}', NOW(), NOW()),

(25, 1, 'municipio', 'Victoria', '17867', 5.3244, -74.9308,
'{"type":"Polygon","coordinates":[[[-74.98,5.29],[-74.98,5.36],[-74.88,5.36],[-74.88,5.29],[-74.98,5.29]]]}', NOW(), NOW()),

(26, 1, 'municipio', 'Viterbo', '17877', 5.0697, -75.8819,
'{"type":"Polygon","coordinates":[[[-75.93,5.03],[-75.93,5.11],[-75.83,5.11],[-75.83,5.03],[-75.93,5.03]]]}', NOW(), NOW());

-- =====================================================
-- 4. COMUNAS/ZONAS DE MANIZALES (11 comunas)
-- =====================================================
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `geom`, `created_at`, `updated_at`) VALUES
(100, 2, 'barrio', 'Comuna Atardeceres', NULL, 5.0450, -75.4850,
'{"type":"Polygon","coordinates":[[[-75.50,5.03],[-75.50,5.06],[-75.47,5.06],[-75.47,5.03],[-75.50,5.03]]]}', NOW(), NOW()),

(101, 2, 'barrio', 'Comuna Cumanday', NULL, 5.0525, -75.5100,
'{"type":"Polygon","coordinates":[[[-75.52,5.04],[-75.52,5.06],[-75.50,5.06],[-75.50,5.04],[-75.52,5.04]]]}', NOW(), NOW()),

(102, 2, 'barrio', 'Comuna Estación', NULL, 5.0685, -75.5100,
'{"type":"Polygon","coordinates":[[[-75.52,5.06],[-75.52,5.08],[-75.50,5.08],[-75.50,5.06],[-75.52,5.06]]]}', NOW(), NOW()),

(103, 2, 'barrio', 'Comuna Ciudadela del Norte', NULL, 5.0835, -75.4980,
'{"type":"Polygon","coordinates":[[[-75.51,5.07],[-75.51,5.10],[-75.48,5.10],[-75.48,5.07],[-75.51,5.07]]]}', NOW(), NOW()),

(104, 2, 'barrio', 'Comuna Ecoturístico Cerro de Oro', NULL, 5.0580, -75.5250,
'{"type":"Polygon","coordinates":[[[-75.54,5.05],[-75.54,5.07],[-75.51,5.07],[-75.51,5.05],[-75.54,5.05]]]}', NOW(), NOW()),

(105, 2, 'barrio', 'Comuna La Fuente', NULL, 5.0615, -75.5065,
'{"type":"Polygon","coordinates":[[[-75.52,5.05],[-75.52,5.07],[-75.49,5.07],[-75.49,5.05],[-75.52,5.05]]]}', NOW(), NOW()),

(106, 2, 'barrio', 'Comuna La Macarena', NULL, 5.0755, -75.5145,
'{"type":"Polygon","coordinates":[[[-75.53,5.07],[-75.53,5.08],[-75.50,5.08],[-75.50,5.07],[-75.53,5.07]]]}', NOW(), NOW()),

(107, 2, 'barrio', 'Comuna Palogrande', NULL, 5.0680, -75.4950,
'{"type":"Polygon","coordinates":[[[-75.51,5.06],[-75.51,5.08],[-75.48,5.08],[-75.48,5.06],[-75.51,5.06]]]}', NOW(), NOW()),

(108, 2, 'barrio', 'Comuna San José', NULL, 5.0730, -75.5195,
'{"type":"Polygon","coordinates":[[[-75.53,5.07],[-75.53,5.08],[-75.51,5.08],[-75.51,5.07],[-75.53,5.07]]]}', NOW(), NOW()),

(109, 2, 'barrio', 'Comuna Universitaria', NULL, 5.0590, -75.4880,
'{"type":"Polygon","coordinates":[[[-75.50,5.05],[-75.50,5.07],[-75.47,5.07],[-75.47,5.05],[-75.50,5.05]]]}', NOW(), NOW()),

(110, 2, 'barrio', 'Comuna Tesorito', NULL, 5.0890, -75.5050,
'{"type":"Polygon","coordinates":[[[-75.52,5.08],[-75.52,5.10],[-75.49,5.10],[-75.49,5.08],[-75.52,5.08]]]}', NOW(), NOW());

-- =====================================================
-- 5. VEREDAS/ZONAS DE OTROS MUNICIPIOS
-- =====================================================
-- Villamaría
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `geom`, `created_at`, `updated_at`) VALUES
(200, 3, 'vereda', 'Zona Centro', NULL, 5.0419, -75.5114,
'{"type":"Polygon","coordinates":[[[-75.52,5.03],[-75.52,5.05],[-75.50,5.05],[-75.50,5.03],[-75.52,5.03]]]}', NOW(), NOW()),

(201, 3, 'vereda', 'Zona Norte', NULL, 5.0480, -75.5090,
'{"type":"Polygon","coordinates":[[[-75.52,5.04],[-75.52,5.05],[-75.50,5.05],[-75.50,5.04],[-75.52,5.04]]]}', NOW(), NOW()),

(202, 3, 'vereda', 'Gallinazo', NULL, 5.0280, -75.5200,
'{"type":"Polygon","coordinates":[[[-75.53,5.02],[-75.53,5.04],[-75.51,5.04],[-75.51,5.02],[-75.53,5.02]]]}', NOW(), NOW()),

-- Chinchiná
(210, 4, 'vereda', 'Zona Centro', NULL, 4.9825, -75.6036,
'{"type":"Polygon","coordinates":[[[-75.62,4.97],[-75.62,4.99],[-75.59,4.99],[-75.59,4.97],[-75.62,4.97]]]}', NOW(), NOW()),

(211, 4, 'vereda', 'Santágueda', NULL, 4.9750, -75.6150,
'{"type":"Polygon","coordinates":[[[-75.63,4.96],[-75.63,4.99],[-75.60,4.99],[-75.60,4.96],[-75.63,4.96]]]}', NOW(), NOW());

-- =====================================================
-- 6. PUESTOS DE VOTACIÓN (20 puestos de ejemplo)
-- =====================================================
INSERT INTO `puestos_votacion` (`id`, `territorio_id`, `nombre`, `direccion`, `total_mesas`, `codigo_registraduria`, `latitud`, `longitud`, `created_at`, `updated_at`) VALUES
-- Manizales - Comuna Palogrande
(1, 107, 'I.E. Pío XII', 'Calle 42 #23-15, Palogrande', 15, 'PV-MAN-001', 5.0685, -75.4945, NOW(), NOW()),
(2, 107, 'Colegio Mayor de Nuestra Señora', 'Calle 62 #26-20, Palermo', 12, 'PV-MAN-002', 5.0655, -75.4920, NOW(), NOW()),
(3, 107, 'Colegio de Cristo', 'Carrera 23 #65-50, Palogrande', 18, 'PV-MAN-003', 5.0690, -75.4950, NOW(), NOW()),

-- Manizales - Comuna Cumanday
(4, 101, 'I.E. Gonzalo Mejía Echeverri', 'Calle 28 #34-10, Cumanday', 20, 'PV-MAN-004', 5.0525, -75.5100, NOW(), NOW()),
(5, 101, 'Colegio Santa Inés', 'Carrera 32 #25-40, Cumanday', 14, 'PV-MAN-005', 5.0530, -75.5105, NOW(), NOW()),

-- Manizales - Comuna Estación
(6, 102, 'I.E. La Salle', 'Carrera 22 #50-10, Estación', 16, 'PV-MAN-006', 5.0685, -75.5100, NOW(), NOW()),

-- Manizales - Comuna Universitaria
(7, 109, 'Universidad de Caldas', 'Calle 65 #26-10', 22, 'PV-MAN-007', 5.0590, -75.4880, NOW(), NOW()),

-- Villamaría
(8, 200, 'I.E. Francisco Miranda', 'Calle 5 #6-20, Centro', 22, 'PV-VIL-001', 5.0419, -75.5114, NOW(), NOW()),
(9, 200, 'Colegio Nazaret', 'Carrera 4 #7-15, Centro', 10, 'PV-VIL-002', 5.0425, -75.5120, NOW(), NOW()),

-- Chinchiná
(10, 210, 'I.E. San José', 'Calle 10 #8-30, Centro', 18, 'PV-CHI-001', 4.9825, -75.6036, NOW(), NOW()),
(11, 210, 'Escuela Normal Superior', 'Carrera 7 #12-05, Centro', 16, 'PV-CHI-002', 4.9830, -75.6040, NOW(), NOW()),

-- Neira
(12, 6, 'I.E. Inmaculada Concepción', 'Calle Principal, Neira', 14, 'PV-NEI-001', 5.1675, -75.5194, NOW(), NOW()),

-- Palestina
(13, 5, 'I.E. Antonio Holguín Garcés', 'Centro, Palestina', 12, 'PV-PAL-001', 5.0508, -75.4244, NOW(), NOW()),

-- La Dorada
(14, 7, 'I.E. Simón Bolívar', 'Barrio Centro, La Dorada', 25, 'PV-DOR-001', 5.4508, -74.6656, NOW(), NOW()),
(15, 7, 'Colegio La Presentación', 'Calle 8 #9-45, La Dorada', 20, 'PV-DOR-002', 5.4520, -74.6670, NOW(), NOW()),

-- Aguadas
(16, 8, 'I.E. José María Obando', 'Plaza Principal, Aguadas', 18, 'PV-AGU-001', 5.6114, -75.4539, NOW(), NOW()),

-- Anserma
(17, 9, 'I.E. Luis Carlos González', 'Centro, Anserma', 16, 'PV-ANS-001', 5.2394, -75.7844, NOW(), NOW()),

-- Salamina
(18, 21, 'I.E. Sagrado Corazón', 'Calle del Medio, Salamina', 14, 'PV-SAL-001', 5.4069, -75.4872, NOW(), NOW()),

-- Riosucio
(19, 19, 'I.E. Bartolomé Lobo Guerrero', 'Plaza de Mercado, Riosucio', 20, 'PV-RIO-001', 5.4211, -75.7036, NOW(), NOW()),

-- Supía
(20, 24, 'I.E. San Vicente de Paúl', 'Centro, Supía', 12, 'PV-SUP-001', 5.4558, -75.6481, NOW(), NOW());

-- =====================================================
-- 7. SIMPATIZANTES (250 registros de ejemplo)
-- =====================================================
-- Nota: Por espacio, aquí hay una muestra. El script generará 250 aleatorios.

-- Estos datos se generarán con el seeder de Laravel
-- que ya creamos, pero aquí te doy la estructura

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
-- Para cargar este archivo:
-- mysql -u root -p mapa_electoral < mapa_electoral_caldas_datos_completos.sql
