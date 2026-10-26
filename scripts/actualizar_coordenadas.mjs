import https from 'https'

function geocode(query, near) {
  return new Promise((resolve) => {
    // near = "lat,lng" para buscar cerca de un punto
    let url = `https://nominatim.openstreetmap.org/search?format=json&limit=3&countrycodes=co&q=${encodeURIComponent(query)}`
    if (near) url += `&viewbox=${near[1]-0.15},${near[0]+0.1},${near[1]+0.15},${near[0]-0.1}&bounded=1`
    const req = https.get(url, { headers: { 'User-Agent': 'MapaElectoralCaldas/1.0' } }, (res) => {
      let data = ''
      res.on('data', c => data += c)
      res.on('end', () => {
        try {
          const json = JSON.parse(data)
          if (json.length > 0) resolve({ lat: parseFloat(json[0].lat), lng: parseFloat(json[0].lon) })
          else resolve(null)
        } catch { resolve(null) }
      })
    })
    req.on('error', () => resolve(null))
    req.setTimeout(10000, () => { req.destroy(); resolve(null) })
  })
}
function sleep(ms) { return new Promise(r => setTimeout(r, ms)) }

// Coordenadas de referencia de los municipios (centro del casco urbano)
const REF = {
  manizales:  [5.0703, -75.5138],
  villamaria: [5.0419, -75.5114],
  chinchina:  [4.9825, -75.6036],
  neira:      [5.1675, -75.5194],
  palestina:  [5.0508, -75.4244],
  la_dorada:  [5.4508, -74.6656],
  aguadas:    [5.6114, -75.4539],
  anserma:    [5.2394, -75.7844],
  salamina:   [5.4069, -75.4872],
  riosucio:   [5.4211, -75.7036],
  supia:      [5.4558, -75.6481],
  marmato:    [5.4761, -75.6711],
}

// Puestos con queries alternativas
const puestos = [
  { id:1,  q:['Colegio Pio XII Manizales', 'Barrio Pio XII Manizales'],           ref: REF.manizales },
  { id:2,  q:['Colegio Mayor Nuestra Señora Manizales'],                          ref: REF.manizales, known:[5.065982,-75.5198182] },
  { id:3,  q:['Colegio Cristo Manizales', 'Colegio de Cristo Carrera Manizales'], ref: REF.manizales, known:[5.0543554,-75.4799721] },
  { id:4,  q:['Colegio Gonzalo Mejia Manizales', 'Gonzalo Mejia Echeverri Manizales'], ref: REF.manizales },
  { id:5,  q:['Colegio Santa Ines Manizales'],                                    ref: REF.manizales, known:[5.0448162,-75.4805343] },
  { id:6,  q:['La Salle Manizales'],                                              ref: REF.manizales, known:[5.0743088,-75.4926233] },
  { id:7,  q:['San Luis Gonzaga Manizales'],                                      ref: REF.manizales, known:[5.0594493,-75.4985068] },
  { id:8,  q:['Universidad Caldas Manizales'],                                    ref: REF.manizales, known:[5.0556,-75.4938881] },
  { id:9,  q:['SENA Manizales'],                                                  ref: REF.manizales, known:[5.0318956,-75.4503366] },
  { id:10, q:['Francisco Jose Caldas escuela Manizales', 'ITFJ Caldas Manizales'], ref: REF.manizales },
  { id:11, q:['La Enea Manizales escuela', 'colegio La Enea Manizales'],          ref: REF.manizales },
  { id:12, q:['Francisco Miranda Villamaria', 'colegio Villamaria Caldas'],       ref: REF.villamaria },
  { id:13, q:['Nazaret Villamaria Caldas'],                                       ref: REF.villamaria },
  { id:14, q:['Playa Rica Villamaria Caldas'],                                    ref: [5.048,-75.509] },
  { id:15, q:['San Jose Chinchina Caldas colegio'],                               ref: REF.chinchina },
  { id:16, q:['Normal Superior Chinchina Caldas'],                                ref: REF.chinchina },
  { id:17, q:['Santagueda Chinchina Caldas'],                                     ref: [4.975,-75.615] },
  { id:18, q:['Inmaculada Neira Caldas colegio'],                                 ref: REF.neira },
  { id:19, q:['Ingruma Neira Caldas'],                                            ref: [5.175,-75.530] },
  { id:20, q:['Antonio Holguin Palestina Caldas'],                                ref: REF.palestina },
  { id:21, q:['Berlin Palestina Caldas vereda'],                                  ref: [5.060,-75.430] },
  { id:22, q:['Simon Bolivar La Dorada Caldas colegio'],                          ref: REF.la_dorada },
  { id:23, q:['La Presentacion La Dorada Caldas colegio'],                        ref: REF.la_dorada },
  { id:24, q:['Juan Jose Rondon La Dorada Caldas'],                               ref: REF.la_dorada },
  { id:25, q:['Jose Maria Obando Aguadas Caldas colegio'],                        ref: REF.aguadas },
  { id:26, q:['Luis Carlos Gonzalez Anserma Caldas colegio'],                     ref: REF.anserma },
  { id:27, q:['Sagrado Corazon Salamina Caldas'],                                 ref: REF.salamina },
  { id:28, q:['Bartolome Lobo Riosucio Caldas'],                                  ref: REF.riosucio },
  { id:29, q:['San Vicente Supia Caldas'],                                        ref: REF.supia },
  { id:30, q:['San Juan Bautista Marmato Caldas'],                                ref: REF.marmato },
]

console.log('Geocodificando puestos de votación...\n')
const sqls = []
const fallbacks = []  // puestos que usarán coordenada de referencia del municipio

for (const p of puestos) {
  // Si ya tiene coordenadas conocidas (de Nominatim anterior), usarlas directamente
  if (p.known) {
    sqls.push(`UPDATE puestos_votacion SET latitud=${p.known[0]}, longitud=${p.known[1]} WHERE id=${p.id};`)
    console.log(`✅ [${p.id}] Coords conocidas: ${p.known[0]}, ${p.known[1]}`)
    continue
  }

  let found = null
  for (const q of p.q) {
    found = await geocode(q, p.ref)
    await sleep(1200)
    if (found) {
      // Verificar que esté a menos de 20km del centro del municipio
      const dist = Math.sqrt(Math.pow(found.lat - p.ref[0], 2) + Math.pow(found.lng - p.ref[1], 2))
      if (dist < 0.2) break  // ~20km en grados
      found = null  // muy lejos, descartar
    }
  }

  if (found) {
    sqls.push(`UPDATE puestos_votacion SET latitud=${found.lat}, longitud=${found.lng} WHERE id=${p.id};`)
    console.log(`✅ [${p.id}] ${found.lat}, ${found.lng}`)
  } else {
    // Usar coordenada de referencia del municipio con pequeño offset aleatorio
    const jitterLat = p.ref[0] + (Math.random() - 0.5) * 0.003
    const jitterLng = p.ref[1] + (Math.random() - 0.5) * 0.003
    sqls.push(`UPDATE puestos_votacion SET latitud=${jitterLat.toFixed(6)}, longitud=${jitterLng.toFixed(6)} WHERE id=${p.id};`)
    fallbacks.push(p.id)
    console.log(`⚠️  [${p.id}] Usando referencia municipio: ${jitterLat.toFixed(6)}, ${jitterLng.toFixed(6)}`)
  }
}

console.log('\n\n=== SQL COMPLETO ===')
sqls.forEach(s => console.log(s))
if (fallbacks.length) console.log(`\n⚠️  IDs con coords aproximadas: ${fallbacks.join(', ')}`)
