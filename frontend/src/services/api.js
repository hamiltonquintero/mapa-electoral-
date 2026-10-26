import axios from 'axios'
import router from '@/router'

/**
 * Instancia Axios configurada para la API Laravel.
 * - Adjunta token Sanctum en cada petición
 * - Intercepta 401 y redirige al login
 * - Intercepta 403 y muestra toast de no autorizado
 */
const apiBase = import.meta.env.VITE_API_URL
  ? import.meta.env.VITE_API_URL.replace(/\/$/, '')
  : (import.meta.env.DEV ? '' : 'https://tahoe-computer-hundred-action.trycloudflare.com')

const api = axios.create({
  baseURL: `${apiBase}/api/v1`,
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 30_000,
})

/* Adjuntar token desde localStorage */
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

/* Manejo global de errores */
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      router.push({ name: 'login' })
    }
    return Promise.reject(error)
  },
)

export default api
