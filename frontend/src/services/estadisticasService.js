import api from './api'

export const estadisticasService = {
  conteosTerritorio: (params = {}) =>
    api.get('/estadisticas/conteos-territorio', { params }),

  conteosPuestoMesa: (params = {}) =>
    api.get('/estadisticas/conteos-puesto-mesa', { params }),

  generales: (params = {}) =>
    api.get('/estadisticas/generales', { params }),

  topTerritorios: (params = {}) =>
    api.get('/estadisticas/top-territorios', { params }),
}
