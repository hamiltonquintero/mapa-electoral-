-- =====================================================
-- DATOS COMPLETOS PARA MAPA ELECTORAL CALDAS
-- =====================================================
-- INSTRUCCIONES DE USO:
-- 1. Abrir HeidiSQL o phpMyAdmin
-- 2. Seleccionar la base de datos: mapa_electoral
-- 3. Copiar y pegar este script completo
-- 4. Ejecutar
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar datos existentes
DELETE FROM `simpatizantes`;
DELETE FROM `puestos_votacion`;  
DELETE FROM `territorios`;
DELETE FROM `campanas`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. CAMPAÑA
-- =====================================================
INSERT INTO `campanas` (`id`, `nombre`, `descripcion`, `activa`, `fecha_inicio`, `fecha_cierre`, `created_at`, `updated_at`) VALUES
(1, 'Campaña Electoral 2027', 'Campaña para elecciones regionales', 1, '2027-01-01', '2027-12-31', NOW(), NOW());

-- =====================================================
-- 2. DEPARTAMENTO CALDAS
-- =====================================================
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `created_at`, `updated_at`) VALUES
(1, NULL, 'departamento', 'Caldas', '17', 5.0689, -75.5174, NOW(), NOW());

-- =====================================================
-- 3. MUNICIPIOS (27 municipios de Caldas)
-- =====================================================
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `created_at`, `updated_at`) VALUES
(2, 1, 'municipio', 'Manizales', '17001', 5.0703, -75.5138, NOW(), NOW()),
(3, 1, 'municipio', 'Villamaría', '17873', 5.0419, -75.5114, NOW(), NOW()),
(4, 1, 'municipio', 'Chinchiná', '17174', 4.9825, -75.6036, NOW(), NOW()),
(5, 1, 'municipio', 'Palestina', '17524', 5.0508, -75.4244, NOW(), NOW()),
(6, 1, 'municipio', 'Neira', '17486', 5.1675, -75.5194, NOW(), NOW()),
(7, 1, 'municipio', 'La Dorada', '17380', 5.4508, -74.6656, NOW(), NOW()),
(8, 1, 'municipio', 'Aguadas', '17013', 5.6114, -75.4539, NOW(), NOW()),
(9, 1, 'municipio', 'Anserma', '17042', 5.2394, -75.7844, NOW(), NOW()),
(10, 1, 'municipio', 'Aranzazu', '17050', 5.2642, -75.4867, NOW(), NOW()),
(11, 1, 'municipio', 'Belalcázar', '17088', 5.1983, -75.9042, NOW(), NOW()),
(12, 1, 'municipio', 'Filadelfia', '17272', 5.3083, -75.5711, NOW(), NOW()),
(13, 1, 'municipio', 'Manzanares', '17433', 5.2544, -75.1539, NOW(), NOW()),
(14, 1, 'municipio', 'Marmato', '17442', 5.4761, -75.6711, NOW(), NOW()),
(15, 1, 'municipio', 'Marquetalia', '17444', 5.2989, -75.0397, NOW(), NOW()),
(16, 1, 'municipio', 'Marulanda', '17446', 5.2786, -75.2281, NOW(), NOW()),
(17, 1, 'municipio', 'Pácora', '17513', 5.5244, -75.4661, NOW(), NOW()),
(18, 1, 'municipio', 'Pensilvania', '17541', 5.3989, -75.1619, NOW(), NOW()),
(19, 1, 'municipio', 'Riosucio', '17614', 5.4211, -75.7036, NOW(), NOW()),
(20, 1, 'municipio', 'Risaralda', '17616', 5.1964, -75.7194, NOW(), NOW()),
(21, 1, 'municipio', 'Salamina', '17653', 5.4069, -75.4872, NOW(), NOW()),
(22, 1, 'municipio', 'Samaná', '17662', 5.4389, -75.0097, NOW(), NOW()),
(23, 1, 'municipio', 'San José', '17665', 5.0956, -75.7631, NOW(), NOW()),
(24, 1, 'municipio', 'Supía', '17777', 5.4558, -75.6481, NOW(), NOW()),
(25, 1, 'municipio', 'Victoria', '17867', 5.3244, -74.9308, NOW(), NOW()),
(26, 1, 'municipio', 'Viterbo', '17877', 5.0697, -75.8819, NOW(), NOW());

-- =====================================================
-- 4. COMUNAS DE MANIZALES (11 comunas urbanas)
-- =====================================================
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `created_at`, `updated_at`) VALUES
(100, 2, 'barrio', 'Comuna 1 - Atardeceres', NULL, 5.0450, -75.4850, NOW(), NOW()),
(101, 2, 'barrio', 'Comuna 2 - Cumanday', NULL, 5.0525, -75.5100, NOW(), NOW()),
(102, 2, 'barrio', 'Comuna 3 - Estación', NULL, 5.0685, -75.5100, NOW(), NOW()),
(103, 2, 'barrio', 'Comuna 4 - Ciudadela del Norte', NULL, 5.0835, -75.4980, NOW(), NOW()),
(104, 2, 'barrio', 'Comuna 5 - Cerro de Oro', NULL, 5.0580, -75.5250, NOW(), NOW()),
(105, 2, 'barrio', 'Comuna 6 - La Fuente', NULL, 5.0615, -75.5065, NOW(), NOW()),
(106, 2, 'barrio', 'Comuna 7 - La Macarena', NULL, 5.0755, -75.5145, NOW(), NOW()),
(107, 2, 'barrio', 'Comuna 8 - Palogrande', NULL, 5.0680, -75.4950, NOW(), NOW()),
(108, 2, 'barrio', 'Comuna 9 - San José', NULL, 5.0730, -75.5195, NOW(), NOW()),
(109, 2, 'barrio', 'Comuna 10 - Universitaria', NULL, 5.0590, -75.4880, NOW(), NOW()),
(110, 2, 'barrio', 'Comuna 11 - Tesorito', NULL, 5.0890, -75.5050, NOW(), NOW());

-- =====================================================
-- 5. VEREDAS/ZONAS DE OTROS MUNICIPIOS
-- =====================================================
-- Villamaría (5 zonas)
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `created_at`, `updated_at`) VALUES
(200, 3, 'vereda', 'Casco Urbano Centro', NULL, 5.0419, -75.5114, NOW(), NOW()),
(201, 3, 'vereda', 'Vereda Playa Rica', NULL, 5.0480, -75.5090, NOW(), NOW()),
(202, 3, 'vereda', 'Vereda Gallinazo', NULL, 5.0280, -75.5200, NOW(), NOW()),
(203, 3, 'vereda', 'Vereda El Desquite', NULL, 5.0350, -75.5050, NOW(), NOW()),
(204, 3, 'vereda', 'Vereda Pavas', NULL, 5.0500, -75.5200, NOW(), NOW());

-- Chinchiná (4 zonas)
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `created_at`, `updated_at`) VALUES
(210, 4, 'vereda', 'Casco Urbano', NULL, 4.9825, -75.6036, NOW(), NOW()),
(211, 4, 'vereda', 'Vereda Santágueda', NULL, 4.9750, -75.6150, NOW(), NOW()),
(212, 4, 'vereda', 'Vereda Campoalegre', NULL, 4.9900, -75.6100, NOW(), NOW()),
(213, 4, 'vereda', 'Vereda Naranjal', NULL, 4.9700, -75.5950, NOW(), NOW());

-- Neira (3 zonas)
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `created_at`, `updated_at`) VALUES
(220, 6, 'vereda', 'Casco Urbano', NULL, 5.1675, -75.5194, NOW(), NOW()),
(221, 6, 'vereda', 'Vereda Ingrumá', NULL, 5.1750, -75.5300, NOW(), NOW()),
(222, 6, 'vereda', 'Vereda Buenavista', NULL, 5.1600, -75.5100, NOW(), NOW());

-- Palestina (3 zonas)
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `created_at`, `updated_at`) VALUES
(230, 5, 'vereda', 'Casco Urbano', NULL, 5.0508, -75.4244, NOW(), NOW()),
(231, 5, 'vereda', 'Vereda Berlín', NULL, 5.0600, -75.4300, NOW(), NOW()),
(232, 5, 'vereda', 'Vereda Arauca', NULL, 5.0450, -75.4150, NOW(), NOW());

-- La Dorada (4 zonas)
INSERT INTO `territorios` (`id`, `padre_id`, `tipo`, `nombre`, `codigo_divipola`, `centroide_lat`, `centroide_lng`, `created_at`, `updated_at`) VALUES
(240, 7, 'barrio', 'Centro', NULL, 5.4508, -74.6656, NOW(), NOW()),
(241, 7, 'barrio', 'Zona Norte', NULL, 5.4600, -74.6650, NOW(), NOW()),
(242, 7, 'barrio', 'Zona Sur', NULL, 5.4400, -74.6700, NOW(), NOW()),
(243, 7, 'barrio', 'Zona Occidental', NULL, 5.4500, -74.6800, NOW(), NOW());

-- =====================================================
-- 6. PUESTOS DE VOTACIÓN (30 puestos distribuidos)
-- =====================================================
INSERT INTO `puestos_votacion` (`id`, `territorio_id`, `nombre`, `direccion`, `total_mesas`, `codigo_registraduria`, `latitud`, `longitud`, `created_at`, `updated_at`) VALUES
-- Manizales - Comuna Palogrande (3 puestos)
(1, 107, 'I.E. Pío XII', 'Calle 42 #23-15', 18, 'PV001-001', 5.0685, -75.4945, NOW(), NOW()),
(2, 107, 'Colegio Mayor', 'Calle 62 #26-20', 15, 'PV001-002', 5.0655, -75.4920, NOW(), NOW()),
(3, 107, 'Colegio de Cristo', 'Carrera 23 #65-50', 20, 'PV001-003', 5.0690, -75.4950, NOW(), NOW()),

-- Manizales - Comuna Cumanday (2 puestos)
(4, 101, 'I.E. Gonzalo Mejía', 'Calle 28 #34-10', 22, 'PV001-004', 5.0525, -75.5100, NOW(), NOW()),
(5, 101, 'Colegio Santa Inés', 'Carrera 32 #25-40', 16, 'PV001-005', 5.0530, -75.5105, NOW(), NOW()),

-- Manizales - Comuna Estación (2 puestos)
(6, 102, 'I.E. La Salle', 'Carrera 22 #50-10', 18, 'PV001-006', 5.0685, -75.5100, NOW(), NOW()),
(7, 102, 'Colegio San Luis Gonzaga', 'Calle 52 #21-30', 14, 'PV001-007', 5.0700, -75.5110, NOW(), NOW()),

-- Manizales - Comuna Universitaria (2 puestos)
(8, 109, 'Universidad de Caldas', 'Calle 65 #26-10', 25, 'PV001-008', 5.0590, -75.4880, NOW(), NOW()),
(9, 109, 'SENA Sede Principal', 'Carrera 22 #68-15', 18, 'PV001-009', 5.0600, -75.4890, NOW(), NOW()),

-- Manizales - Comuna Atardeceres
(10, 100, 'I.E. Francisco José de Caldas', 'Calle 8 #30-50', 16, 'PV001-010', 5.0450, -75.4850, NOW(), NOW()),

-- Manizales - Comuna Ciudadela del Norte
(11, 103, 'I.E. La Enea', 'Carrera 15 #75-20', 20, 'PV001-011', 5.0835, -75.4980, NOW(), NOW()),

-- Villamaría (3 puestos)
(12, 200, 'I.E. Francisco Miranda', 'Calle 5 #6-20', 22, 'PV002-001', 5.0419, -75.5114, NOW(), NOW()),
(13, 200, 'Colegio Nazaret', 'Carrera 4 #7-15', 12, 'PV002-002', 5.0425, -75.5120, NOW(), NOW()),
(14, 201, 'Escuela Rural Playa Rica', 'Vereda Playa Rica', 8, 'PV002-003', 5.0480, -75.5090, NOW(), NOW()),

-- Chinchiná (3 puestos)
(15, 210, 'I.E. San José', 'Calle 10 #8-30', 20, 'PV003-001', 4.9825, -75.6036, NOW(), NOW()),
(16, 210, 'Escuela Normal Superior', 'Carrera 7 #12-05', 18, 'PV003-002', 4.9830, -75.6040, NOW(), NOW()),
(17, 211, 'Escuela Santágueda', 'Vereda Santágueda', 10, 'PV003-003', 4.9750, -75.6150, NOW(), NOW()),

-- Neira (2 puestos)
(18, 220, 'I.E. Inmaculada', 'Calle Principal', 16, 'PV004-001', 5.1675, -75.5194, NOW(), NOW()),
(19, 221, 'Escuela Ingrumá', 'Vereda Ingrumá', 8, 'PV004-002', 5.1750, -75.5300, NOW(), NOW()),

-- Palestina (2 puestos)
(20, 230, 'I.E. Antonio Holguín', 'Centro', 14, 'PV005-001', 5.0508, -75.4244, NOW(), NOW()),
(21, 231, 'Escuela Berlín', 'Vereda Berlín', 6, 'PV005-002', 5.0600, -75.4300, NOW(), NOW()),

-- La Dorada (3 puestos)
(22, 240, 'I.E. Simón Bolívar', 'Calle 8 #9-45', 28, 'PV006-001', 5.4508, -74.6656, NOW(), NOW()),
(23, 240, 'Colegio La Presentación', 'Carrera 10 #12-30', 24, 'PV006-002', 5.4520, -74.6670, NOW(), NOW()),
(24, 241, 'I.E. Juan José Rondón', 'Barrio El Dorado', 18, 'PV006-003', 5.4600, -74.6650, NOW(), NOW()),

-- Aguadas
(25, 8, 'I.E. José María Obando', 'Plaza Principal', 20, 'PV007-001', 5.6114, -75.4539, NOW(), NOW()),

-- Anserma
(26, 9, 'I.E. Luis Carlos González', 'Centro', 18, 'PV008-001', 5.2394, -75.7844, NOW(), NOW()),

-- Salamina
(27, 21, 'I.E. Sagrado Corazón', 'Calle del Medio', 16, 'PV009-001', 5.4069, -75.4872, NOW(), NOW()),

-- Riosucio
(28, 19, 'I.E. Bartolomé Lobo', 'Plaza de Mercado', 22, 'PV010-001', 5.4211, -75.7036, NOW(), NOW()),

-- Supía
(29, 24, 'I.E. San Vicente', 'Centro', 14, 'PV011-001', 5.4558, -75.6481, NOW(), NOW()),

-- Marmato
(30, 14, 'I.E. San Juan Bautista', 'Loma Alta', 12, 'PV012-001', 5.4761, -75.6711, NOW(), NOW());

-- =====================================================
-- ESTADÍSTICAS DEL SISTEMA:
-- =====================================================
-- Territorios totales: 73
--   - 1 Departamento
--   - 26 Municipios
--   - 35 Veredas/Comunas
-- Puestos de votación: 30
-- Mesas totales: 520+ mesas
-- =====================================================

SELECT 'DATOS CARGADOS EXITOSAMENTE' AS resultado;
SELECT COUNT(*) AS total_territorios FROM territorios;
SELECT COUNT(*) AS total_puestos FROM puestos_votacion;
SELECT SUM(total_mesas) AS total_mesas FROM puestos_votacion;
