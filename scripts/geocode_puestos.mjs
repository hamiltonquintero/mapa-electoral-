import https from 'https'

function geocode(query) {
  return new Promise((resolve) => {
    const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=co&q=${encodeURIComponent(query)}`
    const req = https.get(url, { headers: { 'User-Agent': 'MapaElectoralCaldas/1.0' } }, (res) => {
      let data = ''
      res.on('data', c => data += c)
      res.on('end', () => {
        try {
          const json = JSON.parse(data)
          if (json.length > 0) {
            resolve({ lat: parseFloat(json[0].lat), lng: parseFloat(json[0].lon), display: json[0].display_name.substring(0,80) })
          } else {
            resolve(null)
          }
        } catch { resolve(null) }
      })
    })
    req.on('error', () => resolve(null))
    req.setTimeout(10000, () => { req.destroy(); resolve(null) })
  })
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)) }

// Puestos de votación reales de Manizales y otros municipios
// Basado en instituciones educativas reales que funcionan como puestos de votación
const puestos = [
  // MANIZALES - Palogrande
  { id: 1, nombre: 'I.E. Pío XII',            query: 'Institución Educativa Pio XII Manizales Caldas Colombia' },
  { id: 2, nombre: 'Colegio Mayor',            query: 'Colegio Mayor de Nuestra Señora Manizales Caldas Colombia' },
  { id: 3, nombre: 'Colegio de Cristo',        query: 'Colegio de Cristo Manizales Caldas Colombia' },
  // MANIZALES - Cumanday
  { id: 4, nombre: 'I.E. Gonzalo Mejía',       query: 'Institución Educativa Gonzalo Mejia Manizales Caldas Colombia' },
  { id: 5, nombre: 'Colegio Santa Inés',       query: 'Colegio Santa Ines Manizales Caldas Colombia' },
  // MANIZALES - Estación
  { id: 6, nombre: 'I.E. La Salle',            query: 'Colegio La Salle Manizales Caldas Colombia' },
  { id: 7, nombre: 'Colegio San Luis Gonzaga', query: 'Colegio San Luis Gonzaga Manizales Caldas Colombia' },
  // MANIZALES - Universitaria
  { id: 8, nombre: 'Universidad de Caldas',    query: 'Universidad de Caldas Manizales Colombia' },
  { id: 9, nombre: 'SENA Sede Principal',      query: 'SENA Manizales Caldas Colombia' },
  // MANIZALES - Atardeceres
  { id: 10, nombre: 'I.E. Francisco José de Caldas', query: 'Instituto Tecnico Francisco Jose de Caldas Manizales Colombia' },
  // MANIZALES - Ciudadela Norte
  { id: 11, nombre: 'I.E. La Enea',            query: 'Institución Educativa La Enea Manizales Caldas Colombia' },
  // VILLAMARÍA
  { id: 12, nombre: 'I.E. Francisco Miranda',  query: 'Institución Educativa Francisco Miranda Villamaria Caldas Colombia' },
  { id: 13, nombre: 'Colegio Nazaret',         query: 'Colegio Nazaret Villamaria Caldas Colombia' },
  { id: 14, nombre: 'Escuela Rural Playa Rica', query: 'Escuela Playa Rica Villamaria Caldas Colombia' },
  // CHINCHINÁ
  { id: 15, nombre: 'I.E. San José Chinchiná', query: 'Institución Educativa San Jose Chinchina Caldas Colombia' },
  { id: 16, nombre: 'Escuela Normal Superior', query: 'Escuela Normal Superior Chinchina Caldas Colombia' },
  { id: 17, nombre: 'Escuela Santágueda',      query: 'Vereda Santagueda Chinchina Caldas Colombia' },
  // NEIRA
  { id: 18, nombre: 'I.E. Inmaculada',         query: 'Institución Educativa Inmaculada Neira Caldas Colombia' },
  { id: 19, nombre: 'Escuela Ingrumá',         query: 'Vereda Ingruma Neira Caldas Colombia' },
  // PALESTINA
  { id: 20, nombre: 'I.E. Antonio Holguín',    query: 'Institución Educativa Antonio Holguin Palestina Caldas Colombia' },
  { id: 21, nombre: 'Escuela Berlín',          query: 'Vereda Berlin Palestina Caldas Colombia' },
  // LA DORADA
  { id: 22, nombre: 'I.E. Simón Bolívar',      query: 'Institución Educativa Simon Bolivar La Dorada Caldas Colombia' },
  { id: 23, nombre: 'Colegio La Presentación', query: 'Colegio La Presentacion La Dorada Caldas Colombia' },
  { id: 24, nombre: 'I.E. Juan José Rondón',   query: 'Institución Educativa Juan Jose Rondon La Dorada Caldas Colombia' },
  // OTROS
  { id: 25, nombre: 'I.E. José María Obando',  query: 'Institución Educativa Jose Maria Obando Aguadas Caldas Colombia' },
  { id: 26, nombre: 'I.E. Luis Carlos González', query: 'Institución Educativa Luis Carlos Gonzalez Anserma Caldas Colombia' },
  { id: 27, nombre: 'I.E. Sagrado Corazón',    query: 'Institución Educativa Sagrado Corazon Salamina Caldas Colombia' },
  { id: 28, nombre: 'I.E. Bartolomé Lobo',     query: 'Institución Educativa Bartolome Lobo Guerrero Riosucio Caldas Colombia' },
  { id: 29, nombre: 'I.E. San Vicente',        query: 'Institución Educativa San Vicente Supia Caldas Colombia' },
  { id: 30, nombre: 'I.E. San Juan Bautista',  query: 'Institución Educativa San Juan Bautista Marmato Caldas Colombia' },
]

console.log('Geocodificando puestos de votación con Nominatim...\n')
const resultados = []

for (const p of puestos) {
  const result = await geocode(p.query)
  if (result) {
    resultados.push({ id: p.id, nombre: p.nombre, lat: result.lat, lng: result.lng })
    console.log(`✅ [${p.id}] ${p.nombre}: ${result.lat}, ${result.lng}`)
    console.log(`   → ${result.display}`)
  } else {
    resultados.push({ id: p.id, nombre: p.nombre, lat: null, lng: null })
    console.log(`❌ [${p.id}] ${p.nombre}: NO ENCONTRADO`)
  }
  await sleep(1200) // respetar límite de Nominatim: 1 req/seg
}

// Generar SQL de actualización
console.log('\n\n=== SQL DE ACTUALIZACIÓN ===')
for (const r of resultados) {
  if (r.lat && r.lng) {
    console.log(`UPDATE puestos_votacion SET latitud=${r.lat}, longitud=${r.lng} WHERE id=${r.id}; -- ${r.nombre}`)
  } else {
    console.log(`-- SIN COORDS: id=${r.id} ${r.nombre}`)
  }
}
