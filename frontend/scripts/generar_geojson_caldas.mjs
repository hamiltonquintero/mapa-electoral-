import { readFileSync, writeFileSync, mkdirSync } from 'fs'
import { createRequire } from 'module'
import https from 'https'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

function fetch(url) {
  return new Promise((resolve, reject) => {
    https.get(url, { headers: { 'User-Agent': 'node' } }, res => {
      if (res.statusCode === 301 || res.statusCode === 302) {
        return fetch(res.headers.location).then(resolve).catch(reject)
      }
      let data = ''
      res.on('data', chunk => data += chunk)
      res.on('end', () => resolve(data))
      res.on('error', reject)
    }).on('error', reject)
  })
}

console.log('Descargando GeoJSON de Colombia...')
const raw = await fetch('https://raw.githubusercontent.com/santiblanko/colombia.geojson/master/mpio.json')
console.log(`Descargado: ${(raw.length / 1024).toFixed(0)} KB`)

const colombia = JSON.parse(raw)
console.log(`Total municipios Colombia: ${colombia.features.length}`)

// Filtrar Caldas DPTO="17"
const caldas = colombia.features.filter(f => f.properties.DPTO === '17')
console.log(`Municipios Caldas: ${caldas.length}`)

// Simplificar propiedades
const features = caldas.map(f => ({
  type: 'Feature',
  properties: {
    codigo_divipola: f.properties.MPIOS,
    nombre: f.properties.NOMBRE_MPI
      .toLowerCase()
      .replace(/(^|\s)\S/g, c => c.toUpperCase()),
    nombre_upper: f.properties.NOMBRE_MPI,
  },
  geometry: f.geometry,
}))

const output = { type: 'FeatureCollection', features }
const outPath = path.join(__dirname, '../public/geojson/caldas_municipios.geojson')
mkdirSync(path.dirname(outPath), { recursive: true })
writeFileSync(outPath, JSON.stringify(output), 'utf8')
console.log(`Guardado: ${outPath}`)
console.log(`Tamaño: ${(JSON.stringify(output).length / 1024).toFixed(1)} KB`)
console.log('Municipios:')
features.forEach(f => console.log(`  ${f.properties.codigo_divipola} - ${f.properties.nombre}`))
