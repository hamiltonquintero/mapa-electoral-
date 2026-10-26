import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const user        = ref(null)
  const token       = ref(localStorage.getItem('auth_token') ?? null)
  const initialized = ref(false)
  /** Usado entre los pasos del login de 2 factores */
  const pendingEmail = ref(null)

  /* ── Computed ─────────────────────────────────────────────── */
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const roles = computed(() =>
    user.value?.roles?.map((r) => r.name) ?? [],
  )

  const permissions = computed(() =>
    user.value?.permissions ?? [],
  )

  const isSuperAdmin = computed(() =>
    roles.value.includes('Superadministrador'),
  )

  /* ── Helpers ──────────────────────────────────────────────── */
  function hasPermission(permission) {
    if (isSuperAdmin.value) return true
    return permissions.value.includes(permission)
  }

  function hasRole(role) {
    return roles.value.includes(role)
  }

  /* ── Actions ──────────────────────────────────────────────── */
  async function init() {
    initialized.value = true
    if (!token.value) return

    try {
      const { data } = await authService.me()
      user.value = data.data ?? data
    } catch {
      // Token inválido — limpiar
      logout()
    }
  }

  async function login(email, password) {
    const { data } = await authService.login({ email, password })

    if (data.requires_2fa) {
      // Guardar el email para el siguiente paso
      pendingEmail.value = email
      return { requires2fa: true, tempToken: data.temp_token }
    }

    _setSession(data)
    return { requires2fa: false }
  }

  async function verify2fa(code, tempToken) {
    const { data } = await authService.verify2fa({ code, temp_token: tempToken })
    _setSession(data)
  }

  async function logout() {
    try { await authService.logout() } catch { /* ignorar */ }
    _clearSession()
  }

  function _setSession(data) {
    token.value = data.token ?? data.access_token
    user.value  = data.user
    localStorage.setItem('auth_token', token.value)
    pendingEmail.value = null
  }

  function _clearSession() {
    token.value = null
    user.value  = null
    localStorage.removeItem('auth_token')
  }

  return {
    user, token, initialized, pendingEmail,
    isAuthenticated, roles, permissions, isSuperAdmin,
    hasPermission, hasRole,
    init, login, verify2fa, logout,
  }
})
