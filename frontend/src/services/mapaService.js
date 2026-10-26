import api from './api'

export const mapaService = {
  /** Resumen del nivel raíz (Caldas) */
  resumen: (params = {}) =>
    api.get('/mapa', { params }),

  /** Conteos de hijos de un territorio */
  hijos: (territorioId, params = {}) =>
    api.get(`/mapa/territorios/${territorioId}/hijos`, { params }),

  /** Jerarquía completa (breadcrumb) de un territorio */
  jerarquia: (territorioId) =>
    api.get(`/mapa/territorios/${territorioId}/jerarquia`),

  /** GeoJSON de polígono de un territorio */
  geojson: (territorioId) =>
    api.get(`/mapa/territorios/${territorioId}/geojson`),

  /** GeoJSON de múltiples territorios */
  geojsonMultiple: (ids) =>
    api.post('/mapa/territorios/geojson-multiple', { ids }),

  /** Puestos de votación de un territorio */
  puestos: (territorioId, params = {}) =>
    api.get(`/mapa/territorios/${territorioId}/puestos`, { params }),

  /** GeoJSON de puestos filtrados */
  puestosGeojson: (params = {}) =>
    api.get('/mapa/puestos/geojson', { params }),

  /** Estadísticas de un territorio */
  estadisticas: (territorioId, params = {}) =>
    api.get(`/mapa/territorios/${territorioId}/estadisticas`, { params }),

  /** Buscar territorios o personas */
  buscar: (q, params = {}) =>
    api.get('/mapa/buscar', { params: { q, ...params } }),

  /** Simpatizantes de un territorio (para el mapa) */
  simpatizantesTerritorio: (territorioId, params = {}) =>
    api.get(`/mapa/territorios/${territorioId}/simpatizantes`, { params }),
}
