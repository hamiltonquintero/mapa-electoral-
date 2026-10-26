import api from './api'

export const usuarioService = {
  list:              (params = {}) => api.get('/usuarios', { params }),
  get:               (id)          => api.get(`/usuarios/${id}`),
  create:            (data)        => api.post('/usuarios', data),
  update:            (id, data)    => api.put(`/usuarios/${id}`, data),
  delete:            (id)          => api.delete(`/usuarios/${id}`),
  asignarRol:        (id, rol)     => api.post(`/usuarios/${id}/rol`, { rol }),
  asignarTerritorios:(id, ids)     => api.post(`/usuarios/${id}/territorios`, { territorios: ids }),
  roles:             ()            => api.get('/usuarios/roles'),
}

export const campanaService = {
  list:   (params = {}) => api.get('/campanas', { params }),
  get:    (id)          => api.get(`/campanas/${id}`),
  create: (data)        => api.post('/campanas', data),
  update: (id, data)    => api.put(`/campanas/${id}`, data),
  delete: (id)          => api.delete(`/campanas/${id}`),
}

export const candidatoService = {
  list:   (params = {}) => api.get('/candidatos', { params }),
  get:    (id)          => api.get(`/candidatos/${id}`),
  create: (data)        => api.post('/candidatos', data),
  update: (id, data)    => api.put(`/candidatos/${id}`, data),
  delete: (id)          => api.delete(`/candidatos/${id}`),
}

export const puestoService = {
  list:   (params = {}) => api.get('/puestos-votacion', { params }),
  get:    (id)          => api.get(`/puestos-votacion/${id}`),
  create: (data)        => api.post('/puestos-votacion', data),
  update: (id, data)    => api.put(`/puestos-votacion/${id}`, data),
  delete: (id)          => api.delete(`/puestos-votacion/${id}`),
}
