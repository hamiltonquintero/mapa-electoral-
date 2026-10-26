/**
 * Genera SQL para poblar la tabla puestos_votacion
 * con todos los colegios reales de Caldas desde OSM,
 * asignados al territorio correcto (municipio o vereda/barrio).
 */

import fs from 'fs'
import path from 'path'

const geojsonPath = path.join(process.cwd(), 'frontend/public/geojson/caldas_colegios.geojson')
const geojson = JSON.parse(fs.readFileSync(geojsonPath, 'utf8'))

console.log(`Total colegios OSM: ${geojson.features.length}`)

// Mapa municipio_id → territorio_id (usar el municipio directamente si no hay subzona)
// Para Manizales usamos las comunas (bboxes aproximados)
const comunasManizales = [
  { id: 100, nombre: 'Atardeceres',       minLat: 4.98, maxLat: 5.06, minLon: -75.52, maxLon: -75.46 },
  { id: 101, nombre: 'Cumanday',          minLat: 5.04, maxLat: 5.07, minLon: -75.54, maxLon: -75.49 },
  { id: 102, nombre: 'Estación',          minLat: 5.06, maxLat: 5.09, minLon: -75.54, maxLon: -75.49 },
  { id: 103, nombre: 'Ciudadela Norte',   minLat: 5.07, maxLat: 5.12, minLon: -75.53, maxLon: -75.47 },
  { id: 104, nombre: 'Cerro de Oro',      minLat: 5.04, maxLat: 5.08, minLon: -75.56, maxLon: -75.52 },
  { id: 105, nombre: 'La Fuente',         minLat: 5.04, maxLat: 5.08, minLon: -75.53, maxLon: -75.49 },
  { id: 106, nombre: 'La Macarena',       minLat: 5.06, maxLat: 5.10, minLon: -75.55, maxLon: -75.50 },
  { id: 107, nombre: 'Palogrande',        minLat: 5.04, maxLat: 5.08, minLon: -75.52, maxLon: -75.47 },
  { id: 108, nombre: 'San José',          minLat: 5.05, maxLat: 5.09, minLon: -75.55, maxLon: -75.51 },
  { id: 109, nombre: 'Universitaria',     minLat: 5.04, maxLat: 5.07, minLon: -75.51, maxLon: -75.47 },
  { id: 110, nombre: 'Tesorito',          minLat: 5.07, maxLat: 5.12, minLon: -75.53, maxLon: -75.48 },
]

function getComunaManizales(lat, lon) {
  for (const c of comunasManizales) {
    if (lat >= c.minLat && lat <= c.maxLat && lon >= c.minLon && lon <= c.maxLon) {
      return c.id
    }
  }
  return 2 // fallback: municipio Manizales
}

// Filtros para tipos relevantes (evitar escuelas rurales muy pequeñas)
const tiposPermitidos = ['school', 'college', 'university']

// Número de mesas estimado por tipo
function getMesas(tipo, nombre) {
  const n = nombre.toLowerCase()
  if (tipo === 'university') return Math.floor(Math.random() * 15) + 20 // 20-35
  if (n.includes('normal') || n.includes('instituto') || n.includes('i.e')) return Math.floor(Math.random() * 12) + 8
  if (tipo === 'college') return Math.floor(Math.random() * 10) + 6
  return Math.floor(Math.random() * 8) + 2 // school básica
}

// Generar registros
const inserts = []
let id = 100 // empezar desde id 100 para no colisionar con los 30 existentes

// Mapa municipio_id → territorio_id (municipio directamente)
const munToTerr = {
  2: 'MANIZALES', 3: 3, 4: 4, 5: 5, 6: 6, 7: 7,
  8: 8, 9: 9, 10: 10, 11: 11, 12: 12, 13: 13,
  14: 14, 15: 15, 16: 16, 17: 17, 18: 18, 19: 19,
  20: 20, 21: 21, 22: 22, 23: 23, 24: 24, 25: 25, 26: 26
}

// Nombres ya existentes (evitar duplicados)
const existentes = new Set([
  'I.E. Pío XII', 'Colegio Mayor', 'Colegio de Cristo',
  'I.E. Gonzalo Mejía', 'Colegio Santa Inés', 'I.E. La Salle',
  'Colegio San Luis Gonzaga', 'Universidad de Caldas', 'SENA Sede Principal',
  'I.E. Francisco José de Caldas', 'I.E. La Enea',
])

const yaUsados = new Set()

for (const feat of geojson.features) {
  const props = feat.properties
  const [lon, lat] = feat.geometry.coordinates
  const nombre = props.nombre.trim()
  
  if (!tiposPermitidos.includes(props.tipo)) continue
  if (yaUsados.has(nombre.toLowerCase())) continue
  yaUsados.add(nombre.toLowerCase())
  
  const munId = props.municipio_id
  if (!munId) continue
  
  let territorioId
  if (munId === 2) {
    // Manizales: asignar a comuna
    territorioId = getComunaManizales(lat, lon)
  } else {
    territorioId = munId
  }
  
  const mesas = getMesas(props.tipo, nombre)
  const nombreSql = nombre.replace(/'/g, "''")
  const municipioSql = props.municipio.replace(/'/g, "''")
  const codigoReg = `PV${props.codigo_divipola}-${String(id).padStart(3,'0')}`
  
  inserts.push(
    `(${id}, ${territorioId}, '${nombreSql}', NULL, ${mesas}, '${codigoReg}', ${lat}, ${lon})`
  )
  id++
}

console.log(`\nPuestos a insertar: ${inserts.length}`)

// Generar el SQL completo
const sqlLines = [
  '-- Eliminar puestos ficticios (ids 1-30)',
  'DELETE FROM simpatizantes WHERE puesto_id BETWEEN 1 AND 30;',
  'DELETE FROM puestos_votacion WHERE id BETWEEN 1 AND 30;',
  '',
  '-- Insertar puestos reales de OSM',
  'INSERT INTO puestos_votacion (id, territorio_id, nombre, direccion, total_mesas, codigo_registraduria, latitud, longitud)',
  'VALUES',
  inserts.join(',\n') + ';',
  '',
  '-- Verificar',
  'SELECT COUNT(*) as total_puestos FROM puestos_votacion;',
  'SELECT SUM(total_mesas) as total_mesas FROM puestos_votacion;',
  '',
  '-- Top 10 por mesas',
  'SELECT nombre, total_mesas, latitud, longitud FROM puestos_votacion ORDER BY total_mesas DESC LIMIT 10;',
]

const sqlFile = path.join(process.cwd(), 'scripts/insertar_puestos_reales.sql')
fs.writeFileSync(sqlFile, sqlLines.join('\n'), 'utf8')
console.log(`\n✅ SQL generado: ${sqlFile}`)
console.log(`   Total puestos: ${inserts.length}`)
console.log(`   Primeras 5 líneas:`)
inserts.slice(0,5).forEach(l => console.log('  ' + l.substring(0,120)))
