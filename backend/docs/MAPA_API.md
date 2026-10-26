# API REST del Mapa Interactivo

API para el mapa interactivo electoral con navegación jerárquica, datos GeoJSON y consultas espaciales PostGIS.

## 🎯 Características

- **Navegación Jerárquica**: Drill-down desde departamento hasta mesa de votación
- **Datos GeoJSON**: Para renderizado en MapLibre GL JS
- **Consultas Espaciales**: Búsqueda por proximidad y contenido geográfico
- **Conteos Dinámicos**: Simpatizantes por territorio, puesto y mesa
- **Filtros por Campaña**: Datos específicos por campaña electoral
- **Control de Acceso**: Solo datos de territorios accesibles por el usuario

## 📍 Endpoints

Todos los endpoints requieren:
- Autenticación: `Bearer {token}`
- Header: `Accept: application/json`
- Middleware: `territory.scope`, `campaign.scope`

### 1. Obtener Territorios Raíz

```http
GET /api/v1/mapa?campana_id={uuid}
```

Retorna los territorios de nivel superior accesibles por el usuario.

**Parámetros Query:**
- `campana_id` (requerido): UUID de la campaña

**Respuesta:**
```json
{
  "data": [
    {
      "id": "uuid",
      "nombre": "Manizales",
      "tipo": "municipio",
      "codigo": "17001",
      "nivel": 2,
      "tiene_hijos": true,
      "conteo": 1250,
      "centroide": {
        "lat": 5.0703,
        "lng": -75.5138
      }
    }
  ],
  "meta": {
    "total": 1
  }
}
```

### 2. Obtener Hijos de un Territorio

```http
GET /api/v1/mapa/territorios/{territorio_id}/hijos?campana_id={uuid}
```

Retorna los territorios hijos (un nivel abajo en la jerarquía).

**Respuesta:**
```json
{
  "data": [
    {
      "id": "uuid",
      "nombre": "Barrio Centro",
      "tipo": "barrio",
      "codigo": "17001-001",
      "nivel": 3,
      "padre_id": "uuid-padre",
      "tiene_hijos": false,
      "conteo": 350,
      "centroide": {
        "lat": 5.0703,
        "lng": -75.5138
      }
    }
  ],
  "padre": {
    "id": "uuid-padre",
    "nombre": "Manizales",
    "tipo": "municipio"
  },
  "meta": {
    "total": 15
  }
}
```

### 3. Obtener Jerarquía (Breadcrumb)

```http
GET /api/v1/mapa/territorios/{territorio_id}/jerarquia
```

Retorna la ruta jerárquica desde la raíz hasta el territorio especificado.

**Respuesta:**
```json
{
  "data": [
    {
      "id": "uuid-1",
      "nombre": "Caldas",
      "tipo": "departamento",
      "nivel": 1
    },
    {
      "id": "uuid-2",
      "nombre": "Manizales",
      "tipo": "municipio",
      "nivel": 2
    },
    {
      "id": "uuid-3",
      "nombre": "Barrio Centro",
      "tipo": "barrio",
      "nivel": 3
    }
  ]
}
```

### 4. Obtener GeoJSON de un Territorio

```http
GET /api/v1/mapa/territorios/{territorio_id}/geojson
```

Retorna la geometría del territorio en formato GeoJSON para renderizado en el mapa.

**Respuesta:**
```json
{
  "type": "Feature",
  "id": "uuid",
  "properties": {
    "nombre": "Manizales",
    "tipo": "municipio",
    "codigo": "17001",
    "nivel": 2,
    "poblacion": 400436
  },
  "geometry": {
    "type": "MultiPolygon",
    "coordinates": [[[[-75.5, 5.0], [-75.4, 5.0], ...]]]
  }
}
```

### 5. Obtener GeoJSON de Múltiples Territorios

```http
POST /api/v1/mapa/territorios/geojson-multiple
Content-Type: application/json
```

**Body:**
```json
{
  "territorio_ids": ["uuid-1", "uuid-2", "uuid-3"],
  "campana_id": "uuid-campana"
}
```

**Respuesta:**
```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "id": "uuid-1",
      "properties": {
        "nombre": "Manizales",
        "tipo": "municipio",
        "codigo": "17001",
        "nivel": 2,
        "poblacion": 400436,
        "conteo": 1250
      },
      "geometry": {
        "type": "MultiPolygon",
        "coordinates": [...]
      }
    }
  ]
}
```

### 6. Obtener Puestos de Votación de un Territorio

```http
GET /api/v1/mapa/territorios/{territorio_id}/puestos?campana_id={uuid}
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": "uuid",
      "nombre": "Colegio San José",
      "codigo": "17001-001",
      "direccion": "Calle 20 # 23-40",
      "total_mesas": 12,
      "ubicacion": {
        "lat": 5.0703,
        "lng": -75.5138
      },
      "conteo": 85
    }
  ],
  "meta": {
    "total": 25
  }
}
```

### 7. Obtener Puestos en Formato GeoJSON

```http
GET /api/v1/mapa/puestos/geojson?territorio_id={uuid}&campana_id={uuid}
```

Retorna los puestos de votación como puntos GeoJSON.

**Respuesta:**
```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "id": "uuid",
      "properties": {
        "nombre": "Colegio San José",
        "codigo": "17001-001",
        "direccion": "Calle 20 # 23-40",
        "total_mesas": 12,
        "conteo": 85
      },
      "geometry": {
        "type": "Point",
        "coordinates": [-75.5138, 5.0703]
      }
    }
  ]
}
```

### 8. Obtener Estadísticas de un Territorio

```http
GET /api/v1/mapa/territorios/{territorio_id}/estadisticas?campana_id={uuid}
```

**Respuesta:**
```json
{
  "territorio_id": "uuid",
  "campana_id": "uuid",
  "total_simpatizantes": 1250,
  "por_estado": {
    "validado": 850,
    "borrador": 300,
    "pendiente_evidencia": 100
  },
  "con_formulario": 950,
  "con_puesto_mesa": 1100,
  "porcentaje_evidencia": 76.0,
  "porcentaje_ubicacion": 88.0
}
```

### 9. Buscar en el Mapa

```http
GET /api/v1/mapa/buscar?q={texto}
GET /api/v1/mapa/buscar?lat={lat}&lng={lng}&radio={metros}
```

Buscar territorios por nombre o por proximidad geográfica.

**Parámetros Query:**
- **Búsqueda por texto:**
  - `q`: Texto a buscar en nombre de territorios
  
- **Búsqueda por proximidad:**
  - `lat`: Latitud
  - `lng`: Longitud
  - `radio` (opcional): Radio en metros (default: 1000)

**Respuesta:**
```json
{
  "data": [
    {
      "id": "uuid",
      "nombre": "Manizales",
      "tipo": "municipio",
      "codigo": "17001",
      "distancia": 150,
      "centroide": {
        "lat": 5.0703,
        "lng": -75.5138
      }
    }
  ]
}
```

## 🗺️ Gestión Administrativa de Territorios

### 10. Listar Territorios

```http
GET /api/v1/territorios?tipo={tipo}&nivel={nivel}&buscar={texto}&por_pagina={n}
```

**Parámetros Query:**
- `tipo` (opcional): departamento, municipio, barrio, vereda, zona
- `nivel` (opcional): 1-5
- `padre_id` (opcional): UUID del padre
- `buscar` (opcional): Búsqueda por nombre
- `orden_campo` (opcional): Campo para ordenar (default: nombre)
- `orden_dir` (opcional): asc/desc (default: asc)
- `por_pagina` (opcional): Resultados por página (default: 50)

**Respuesta:**
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 150
  },
  "links": {
    "first": "...",
    "last": "...",
    "next": "..."
  }
}
```

### 11. Ver un Territorio

```http
GET /api/v1/territorios/{id}
```

**Respuesta:**
```json
{
  "data": {
    "id": "uuid",
    "nombre": "Manizales",
    "tipo": "municipio",
    "codigo": "17001",
    "nivel": 2,
    "poblacion": 400436,
    "padre_id": "uuid-padre",
    "padre": {
      "id": "uuid-padre",
      "nombre": "Caldas",
      "tipo": "departamento"
    },
    "centroide": {
      "lat": 5.0703,
      "lng": -75.5138
    },
    "tiene_hijos": true,
    "hijos_count": 15,
    "total_simpatizantes": 1250,
    "created_at": "2024-01-01T00:00:00.000Z",
    "updated_at": "2024-01-01T00:00:00.000Z"
  }
}
```

### 12. Crear Territorio

```http
POST /api/v1/territorios
Content-Type: application/json
Authorization: Bearer {token}
```

**Permisos:** `territorio.crear`

**Body:**
```json
{
  "nombre": "Barrio La Enea",
  "tipo": "barrio",
  "codigo": "17001-015",
  "padre_id": "uuid-manizales",
  "nivel": 3,
  "poblacion": 25000,
  "geom_wkt": "MULTIPOLYGON(((-75.5 5.0, -75.4 5.0, ...)))",
  "centroide_lat": 5.0703,
  "centroide_lng": -75.5138
}
```

**Campos:**
- `nombre` (requerido): Nombre del territorio
- `tipo` (requerido): departamento, municipio, barrio, vereda, zona
- `codigo` (opcional): Código único
- `padre_id` (opcional): UUID del territorio padre
- `nivel` (requerido): 1-5
- `poblacion` (opcional): Población estimada
- `geom_wkt` (opcional): Geometría en formato Well-Known Text
- `centroide_lat` (opcional): Latitud del centroide
- `centroide_lng` (opcional): Longitud del centroide

**Respuesta:**
```json
{
  "message": "Territorio creado exitosamente",
  "data": {...}
}
```

### 13. Actualizar Territorio

```http
PUT /api/v1/territorios/{id}
Content-Type: application/json
```

**Permisos:** `territorio.actualizar`

Mismo body que crear, todos los campos opcionales.

### 14. Eliminar Territorio

```http
DELETE /api/v1/territorios/{id}
```

**Permisos:** `territorio.eliminar`

**Restricciones:**
- No puede tener hijos
- No puede tener simpatizantes registrados

**Respuesta:**
```json
{
  "message": "Territorio eliminado exitosamente"
}
```

### 15. Obtener Árbol Jerárquico Completo

```http
GET /api/v1/territorios/arbol
```

Retorna todos los territorios accesibles en estructura de árbol jerárquico.

**Respuesta:**
```json
{
  "data": [
    {
      "id": "uuid-1",
      "nombre": "Caldas",
      "tipo": "departamento",
      "codigo": "17",
      "nivel": 1,
      "hijos": [
        {
          "id": "uuid-2",
          "nombre": "Manizales",
          "tipo": "municipio",
          "codigo": "17001",
          "nivel": 2,
          "hijos": [
            {
              "id": "uuid-3",
              "nombre": "Barrio Centro",
              "tipo": "barrio",
              "codigo": "17001-001",
              "nivel": 3,
              "hijos": []
            }
          ]
        }
      ]
    }
  ]
}
```

## 🔒 Control de Acceso

### Alcance Territorial

Los usuarios solo pueden acceder a:
- Territorios **directamente asignados**
- Todos los territorios **descendientes** (calculados con función PostgreSQL)

**Superadministradores** tienen acceso completo a todos los territorios.

### Alcance por Campaña

Todos los endpoints que retornan conteos requieren `campana_id` y verifican:
- Usuario tiene acceso a la campaña especificada
- Solo se cuentan simpatizantes de esa campaña

## 🗺️ Integración con MapLibre GL JS

### Ejemplo: Renderizar Territorios

```javascript
// 1. Obtener territorios raíz
const response = await fetch('/api/v1/mapa?campana_id=' + campanaId, {
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
});
const { data } = await response.json();

// 2. Centrar mapa en el primer territorio
if (data.length > 0) {
  map.flyTo({
    center: [data[0].centroide.lng, data[0].centroide.lat],
    zoom: 10
  });
}

// 3. Obtener GeoJSON de territorios
const geojsonResponse = await fetch('/api/v1/mapa/territorios/geojson-multiple', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    territorio_ids: data.map(t => t.id),
    campana_id: campanaId
  })
});
const geojson = await geojsonResponse.json();

// 4. Agregar capa al mapa
map.addSource('territorios', {
  type: 'geojson',
  data: geojson
});

map.addLayer({
  id: 'territorios-fill',
  type: 'fill',
  source: 'territorios',
  paint: {
    'fill-color': [
      'interpolate',
      ['linear'],
      ['get', 'conteo'],
      0, '#f0f0f0',
      100, '#fee5d9',
      500, '#fc8d59',
      1000, '#e34a33',
      2000, '#b30000'
    ],
    'fill-opacity': 0.7
  }
});

map.addLayer({
  id: 'territorios-line',
  type: 'line',
  source: 'territorios',
  paint: {
    'line-color': '#333',
    'line-width': 2
  }
});
```

### Ejemplo: Drill-Down al Hacer Click

```javascript
map.on('click', 'territorios-fill', async (e) => {
  const territorioId = e.features[0].id;
  
  // Obtener hijos del territorio
  const response = await fetch(
    `/api/v1/mapa/territorios/${territorioId}/hijos?campana_id=${campanaId}`,
    {
      headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
      }
    }
  );
  const { data } = await response.json();
  
  if (data.length > 0) {
    // Tiene hijos, hacer zoom
    const geojsonResponse = await fetch('/api/v1/mapa/territorios/geojson-multiple', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        territorio_ids: data.map(t => t.id),
        campana_id: campanaId
      })
    });
    const geojson = await geojsonResponse.json();
    
    // Actualizar source del mapa
    map.getSource('territorios').setData(geojson);
    
    // Hacer zoom al territorio
    const bounds = turf.bbox(geojson);
    map.fitBounds(bounds, { padding: 50 });
  } else {
    // No tiene hijos, mostrar puestos de votación
    const puestosResponse = await fetch(
      `/api/v1/mapa/puestos/geojson?territorio_id=${territorioId}&campana_id=${campanaId}`,
      {
        headers: {
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json'
        }
      }
    );
    const puestosGeojson = await puestosResponse.json();
    
    // Agregar puestos como puntos
    map.addSource('puestos', {
      type: 'geojson',
      data: puestosGeojson
    });
    
    map.addLayer({
      id: 'puestos',
      type: 'circle',
      source: 'puestos',
      paint: {
        'circle-radius': [
          'interpolate',
          ['linear'],
          ['get', 'conteo'],
          0, 5,
          50, 10,
          100, 15
        ],
        'circle-color': '#007cbf',
        'circle-stroke-width': 2,
        'circle-stroke-color': '#fff'
      }
    });
  }
});
```

## 🔍 Consultas Espaciales

El sistema usa PostGIS para consultas espaciales eficientes:

### Funciones PostgreSQL Disponibles

- `obtener_descendientes_territorio(territorio_id)`: Retorna todos los descendientes de un territorio
- `obtener_jerarquia_territorio(territorio_id)`: Retorna la jerarquía completa (breadcrumb)
- `ST_Distance(geometry, geometry)`: Calcular distancia entre geometrías
- `ST_DWithin(geography, geography, distance)`: Verificar si está dentro de un radio
- `ST_Contains(geometry, geometry)`: Verificar si una geometría contiene otra
- `ST_Intersects(geometry, geometry)`: Verificar si geometrías se intersectan

## ⚡ Rendimiento

### Optimizaciones Implementadas

1. **Índices Espaciales**: En todas las columnas de geometría
2. **Caché de Territorios Accesibles**: Calculado una vez por request en middleware
3. **Eager Loading**: Relaciones pre-cargadas con `with()`
4. **Conteos Agregados**: Descendientes incluidos en conteos de padres
5. **Vistas Materializadas**: Para estadísticas frecuentes (refrescadas cada 5 min)

### Recomendaciones

- Usar `geojson-multiple` en lugar de múltiples llamadas a `geojson`
- Paginar resultados con `por_pagina` cuando hay muchos territorios
- Cachear geometrías en el cliente (no cambian frecuentemente)
- Usar `incluir_geometria=0` cuando no se necesita el WKT completo

## 🐛 Errores Comunes

### 400 Bad Request - Campaña requerida

```json
{
  "message": "Debe especificar una campaña válida"
}
```

**Solución**: Incluir `campana_id` en query string o body.

### 403 Forbidden - Sin acceso al territorio

```json
{
  "message": "No tiene acceso a este territorio"
}
```

**Solución**: El usuario no tiene asignado ese territorio ni sus padres. Verificar asignaciones en `usuario_territorio`.

### 404 Not Found

```json
{
  "message": "No query results for model [Territorio] uuid"
}
```

**Solución**: El territorio no existe. Verificar UUID.

## 📚 Recursos Relacionados

- **Control de Acceso Territorial**: `docs/TERRITORIAL_AUTHORIZATION.md`
- **Roles y Permisos**: `docs/ROLES_AND_PERMISSIONS.md`
- **Esquema de Base de Datos**: `database/DATABASE_SCHEMA.md`
- **MapLibre GL JS**: https://maplibre.org/maplibre-gl-js-docs/
- **GeoJSON Spec**: https://geojson.org/
- **PostGIS**: https://postgis.net/documentation/
