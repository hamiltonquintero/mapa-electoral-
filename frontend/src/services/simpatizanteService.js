import api from './api'

export const simpatizanteService = {
  list: (params = {}) =>
    api.get('/simpatizantes', { params }),

  get: (id) =>
    api.get(`/simpatizantes/${id}`),

  create: (data) =>
    api.post('/simpatizantes', data),

  update: (id, data) =>
    api.put(`/simpatizantes/${id}`, data),

  delete: (id) =>
    api.delete(`/simpatizantes/${id}`),

  buscarDuplicados: (cedula, campaniaId) =>
    api.post('/simpatizantes/buscar-duplicados', { cedula, campana_id: campaniaId }),

  cambiarEstado: (id, estado, motivo) =>
    api.post(`/simpatizantes/${id}/cambiar-estado`, { estado, motivo }),

  asignarPuestoMesa: (id, puestoId, mesa) =>
    api.post(`/simpatizantes/${id}/asignar-puesto-mesa`, { puesto_id: puestoId, mesa }),

  asignarLider: (id, liderId) =>
    api.post(`/simpatizantes/${id}/asignar-lider`, { lider_id: liderId }),
}
