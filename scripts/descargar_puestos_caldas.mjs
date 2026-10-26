/**
 * Descarga todos los colegios/escuelas de Caldas desde OpenStreetMap (Overpass API)
 * y los convierte en puestos de votación con coordenadas exactas.
 * 
 * Estrategia:
 * 1. Descargar todos los amenity=school|college|university de Caldas desde OSM
 * 2. Cruzar con los municipios de Caldas por código DIVIPOLA
 * 3. Generar SQL de INSERT para puestos_votacion con coordenadas reales
 */

import https from 'https'
import fs from 'fs'
import path from 'path'

function httpGet(url) {
  return new Promise((resolve, reject) => {
    const req = https.get(url, { headers: { 'User-Agent': 'MapaElectoralCaldas/1.0' } }, res => {
      if (res.statusCode >= 300 && res.statusCode < 400) {
        return httpGet(res.headers.location).then(resolve).catch(reject)
      }
      let data = ''
      res.on('data', c => data += c)
      res.on('end', () => resolve(data))
      res.on('error', reject)
    })
    req.on('error', reject)
    req.setTimeout(60000, () => { req.destroy(); reject(new Error('timeout')) })
  })
}

// Overpass QL: todas las escuelas, colegios y universidades de Caldas (dpto 17)
// Usamos el bbox de Caldas: lat 4.6-5.7, lon -76.2 a -74.7
const overpassQuery = `
[out:json][timeout:60];
(
  node["amenity"~"school|college|university"]["name"]
    (4.6,-76.2,5.7,-74.7);
  way["amenity"~"school|college|university"]["name"]
    (4.6,-76.2,5.7,-74.7);
);
out center;
`

const url = 'https://overpass-api.de/api/interpreter?data=' + encodeURIComponent(overpassQuery)
console.log('Descargando colegios de Caldas desde OpenStreetMap...')

let raw
try {
  raw = await httpGet(url)
} catch(e) {
  console.error('Error en Overpass:', e.message)
  process.exit(1)
}

const data = JSON.parse(raw)
console.log(`Total elementos OSM: ${data.elements.length}`)

// Mapeo de municipios Caldas (código DIVIPOLA → nombre, id en BD)
const municipios = {
  '17001': { nombre: 'Manizales',   id: 2 },
  '17013': { nombre: 'Aguadas',     id: 8 },
  '17042': { nombre: 'Anserma',     id: 9 },
  '17050': { nombre: 'Aranzazu',    id: 10 },
  '17088': { nombre: 'Belalcázar',  id: 11 },
  '17174': { nombre: 'Chinchiná',   id: 4 },
  '17272': { nombre: 'Filadelfia',  id: 12 },
  '17380': { nombre: 'La Dorada',   id: 7 },
  '17388': { nombre: 'La Merced',   id: null },
  '17433': { nombre: 'Manzanares',  id: 13 },
  '17442': { nombre: 'Marmato',     id: 14 },
  '17444': { nombre: 'Marquetalia', id: 15 },
  '17446': { nombre: 'Marulanda',   id: 16 },
  '17486': { nombre: 'Neira',       id: 6 },
  '17495': { nombre: 'Norcasia',    id: null },
  '17513': { nombre: 'Pácora',      id: 17 },
  '17524': { nombre: 'Palestina',   id: 5 },
  '17541': { nombre: 'Pensilvania', id: 18 },
  '17614': { nombre: 'Riosucio',    id: 19 },
  '17616': { nombre: 'Risaralda',   id: 20 },
  '17653': { nombre: 'Salamina',    id: 21 },
  '17662': { nombre: 'Samaná',      id: 22 },
  '17665': { nombre: 'San José',    id: 23 },
  '17777': { nombre: 'Supía',       id: 24 },
  '17867': { nombre: 'Victoria',    id: 25 },
  '17873': { nombre: 'Villamaría',  id: 3 },
  '17877': { nombre: 'Viterbo',     id: 26 },
}

// Función para determinar municipio por bbox
function getMunicipioPorCoords(lat, lon) {
  // Bboxes aproximados de los municipios de Caldas
  const bboxes = {
    '17001': { minLat: 4.95, maxLat: 5.20, minLon: -75.65, maxLon: -75.38 }, // Manizales
    '17013': { minLat: 5.53, maxLat: 5.75, minLon: -75.58, maxLon: -75.35 }, // Aguadas
    '17042': { minLat: 5.15, maxLat: 5.35, minLon: -75.90, maxLon: -75.70 }, // Anserma
    '17050': { minLat: 5.20, maxLat: 5.35, minLon: -75.60, maxLon: -75.40 }, // Aranzazu
    '17088': { minLat: 5.10, maxLat: 5.35, minLon: -76.00, maxLon: -75.83 }, // Belalcázar
    '17174': { minLat: 4.92, maxLat: 5.05, minLon: -75.68, maxLon: -75.53 }, // Chinchiná
    '17272': { minLat: 5.23, maxLat: 5.42, minLon: -75.68, maxLon: -75.50 }, // Filadelfia
    '17380': { minLat: 5.35, maxLat: 5.55, minLon: -74.80, maxLon: -74.55 }, // La Dorada
    '17433': { minLat: 5.18, maxLat: 5.35, minLon: -75.25, maxLon: -75.08 }, // Manzanares
    '17442': { minLat: 5.40, maxLat: 5.55, minLon: -75.73, maxLon: -75.58 }, // Marmato
    '17444': { minLat: 5.22, maxLat: 5.40, minLon: -75.12, maxLon: -74.95 }, // Marquetalia
    '17446': { minLat: 5.20, maxLat: 5.38, minLon: -75.33, maxLon: -75.15 }, // Marulanda
    '17486': { minLat: 5.11, maxLat: 5.28, minLon: -75.60, maxLon: -75.45 }, // Neira
    '17495': { minLat: 5.48, maxLat: 5.60, minLon: -74.85, maxLon: -74.65 }, // Norcasia
    '17513': { minLat: 5.45, maxLat: 5.65, minLon: -75.58, maxLon: -75.40 }, // Pácora
    '17524': { minLat: 5.00, maxLat: 5.12, minLon: -75.50, maxLon: -75.36 }, // Palestina
    '17541': { minLat: 5.32, maxLat: 5.50, minLon: -75.25, maxLon: -75.08 }, // Pensilvania
    '17614': { minLat: 5.32, maxLat: 5.55, minLon: -75.82, maxLon: -75.63 }, // Riosucio
    '17616': { minLat: 5.11, maxLat: 5.32, minLon: -75.83, maxLon: -75.65 }, // Risaralda
    '17653': { minLat: 5.33, maxLat: 5.50, minLon: -75.57, maxLon: -75.43 }, // Salamina
    '17662': { minLat: 5.35, maxLat: 5.55, minLon: -75.12, maxLon: -74.85 }, // Samaná
    '17665': { minLat: 5.01, maxLat: 5.21, minLon: -75.85, maxLon: -75.67 }, // San José
    '17777': { minLat: 5.38, maxLat: 5.53, minLon: -75.73, maxLon: -75.57 }, // Supía
    '17867': { minLat: 5.25, maxLat: 5.42, minLon: -75.02, maxLon: -74.82 }, // Victoria
    '17873': { minLat: 4.98, maxLat: 5.10, minLon: -75.58, maxLon: -75.45 }, // Villamaría
    '17877': { minLat: 4.99, maxLat: 5.16, minLon: -75.97, maxLon: -75.79 }, // Viterbo
  }
  
  for (const [codigo, bbox] of Object.entries(bboxes)) {
    if (lat >= bbox.minLat && lat <= bbox.maxLat && lon >= bbox.minLon && lon <= bbox.maxLon) {
      return codigo
    }
  }
  return null
}

// Procesar elementos y filtrar/organizar por municipio
const porMunicipio = {}
let totalValidos = 0

for (const el of data.elements) {
  const lat = el.lat ?? el.center?.lat
  const lon = el.lon ?? el.center?.lon
  if (!lat || !lon || !el.tags?.name) continue
  
  const codigo = el.tags.divipola ?? getMunicipioPorCoords(lat, lon)
  if (!codigo || !municipios[codigo]) continue
  
  const mun = municipios[codigo]
  if (!porMunicipio[codigo]) porMunicipio[codigo] = []
  
  porMunicipio[codigo].push({
    nombre: el.tags.name,
    tipo: el.tags.amenity,
    lat,
    lon,
    municipio: mun.nombre,
    municipioId: mun.id,
    codigo,
    osmId: el.id,
  })
  totalValidos++
}

console.log(`\nColegios válidos en Caldas: ${totalValidos}`)
console.log('\nDistribución por municipio:')
for (const [cod, items] of Object.entries(porMunicipio)) {
  console.log(`  ${municipios[cod].nombre}: ${items.length} establecimientos`)
}

// Generar GeoJSON para el mapa
const features = []
for (const items of Object.values(porMunicipio)) {
  for (const item of items) {
    features.push({
      type: 'Feature',
      properties: {
        nombre: item.nombre,
        tipo: item.tipo,
        municipio: item.municipio,
        municipio_id: item.municipioId,
        codigo_divipola: item.codigo,
        osm_id: item.osmId,
      },
      geometry: { type: 'Point', coordinates: [item.lon, item.lat] }
    })
  }
}

const geojson = { type: 'FeatureCollection', features }
const outGeojson = path.join(process.cwd(), 'frontend/public/geojson/caldas_colegios.geojson')
fs.writeFileSync(outGeojson, JSON.stringify(geojson), 'utf8')
console.log(`\n✅ GeoJSON guardado: ${outGeojson} (${features.length} establecimientos)`)

// Generar SQL para insertar/actualizar puestos de votación
// Solo los establecimientos que tienen nombre similar a los ya existentes en BD
console.log('\n=== PUESTOS IDENTIFICADOS PARA EL SISTEMA ===')
let sqlUpdates = []

// Para Manizales, identificar los colegios principales
const manizales = porMunicipio['17001'] ?? []
const nombresRelevantes = [
  { patron: /la salle/i,         id: 6 },
  { patron: /cristo/i,           id: 3 },
  { patron: /mayor.*nuestra|nuestra.*señora/i, id: 2 },
  { patron: /caldas.*universid|universid.*caldas/i, id: 8 },
  { patron: /sena/i,             id: 9 },
  { patron: /enea/i,             id: 11 },
  { patron: /gonzaga/i,          id: 7 },
  { patron: /gonzalo.*mej|mej.*gonzalo/i, id: 4 },
  { patron: /francisco.*calder|calder.*francisco/i, id: 10 },
  { patron: /palogrande/i,       id: 1 },
]

for (const inst of manizales) {
  for (const { patron, id } of nombresRelevantes) {
    if (patron.test(inst.nombre)) {
      sqlUpdates.push(`UPDATE puestos_votacion SET latitud=${inst.lat}, longitud=${inst.lon}, nombre='${inst.nombre.replace(/'/g, "''")}' WHERE id=${id};`)
      console.log(`  ✅ id=${id}: "${inst.nombre}" → ${inst.lat}, ${inst.lon}`)
      break
    }
  }
}

if (sqlUpdates.length > 0) {
  console.log('\n=== SQL ===')
  sqlUpdates.forEach(s => console.log(s))
}

console.log(`\n📊 Resumen final:`)
console.log(`   Establecimientos OSM en Caldas: ${totalValidos}`)
console.log(`   Puestos actualizados con coords exactas: ${sqlUpdates.length}`)
console.log(`   GeoJSON disponible en: /geojson/caldas_colegios.geojson`)
