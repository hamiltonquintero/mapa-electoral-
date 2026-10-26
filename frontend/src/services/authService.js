import api from './api'

export const authService = {
  login: (credentials) =>
    api.post('/auth/login', credentials),

  verify2fa: (payload) =>
    api.post('/auth/verify-2fa', payload),

  logout: () =>
    api.post('/auth/logout'),

  me: () =>
    api.get('/auth/me'),

  setup2fa: () =>
    api.post('/2fa/setup'),

  enable2fa: (code) =>
    api.post('/2fa/enable', { code }),

  disable2fa: (code) =>
    api.post('/2fa/disable', { code }),

  status2fa: () =>
    api.get('/2fa/status'),
}
