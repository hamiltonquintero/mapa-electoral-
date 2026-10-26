<template>
  <div class="min-h-full flex flex-col justify-center items-center bg-gray-50 p-4">
    <div class="w-full max-w-sm">
      <!-- Logo / marca -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-600 shadow-lg mb-4">
          <MapPinIcon class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Mapa Electoral</h1>
        <p class="text-sm text-gray-500 mt-1">Departamento de Caldas</p>
      </div>

      <!-- Formulario -->
      <div class="card p-6">
        <form @submit.prevent="handleLogin" novalidate>
          <div class="space-y-4">
            <div>
              <label for="email" class="label">Correo electrónico</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                class="input"
                :class="{ 'input-error': errors.email }"
                placeholder="usuario@ejemplo.com"
                required
              />
              <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email }}</p>
            </div>

            <div>
              <label for="password" class="label">Contraseña</label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  class="input pr-10"
                  :class="{ 'input-error': errors.password }"
                  placeholder="••••••••"
                  required
                />
                <button
                  type="button"
                  class="absolute inset-y-0 right-2 flex items-center px-1 text-gray-400 hover:text-gray-600"
                  @click="showPassword = !showPassword"
                >
                  <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                  <EyeSlashIcon v-else class="w-4 h-4" />
                </button>
              </div>
              <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password }}</p>
            </div>
          </div>

          <!-- Error global -->
          <div v-if="errors.general" class="mt-4 rounded-md bg-red-50 border border-red-200 p-3">
            <p class="text-sm text-red-700">{{ errors.general }}</p>
          </div>

          <button type="submit" class="btn-primary w-full mt-6" :disabled="loading">
            <LoadingSpinner v-if="loading" size="sm" />
            <span>{{ loading ? 'Iniciando sesión…' : 'Iniciar sesión' }}</span>
          </button>
        </form>
      </div>

      <p class="text-center text-xs text-gray-400 mt-6">
        Sistema de uso restringido — Solo personal autorizado
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { MapPinIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

const router = useRouter()
const auth   = useAuthStore()

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '', general: '' })
const loading      = ref(false)
const showPassword = ref(false)

function validate() {
  errors.email   = ''
  errors.password = ''
  errors.general = ''

  if (!form.email)    errors.email    = 'El correo es obligatorio'
  if (!form.password) errors.password = 'La contraseña es obligatoria'

  return !errors.email && !errors.password
}

async function handleLogin() {
  if (!validate() || loading.value) return

  loading.value = true
  try {
    const result = await auth.login(form.email, form.password)

    if (result.requires2fa) {
      router.push({ name: 'login-2fa', query: { temp: result.tempToken } })
    } else {
      const redirect = router.currentRoute.value.query.redirect
      router.push(redirect ? String(redirect) : '/mapa')
    }
  } catch (e) {
    const status = e?.response?.status
    if (status === 422) {
      errors.general = 'Correo o contraseña incorrectos'
    } else if (status === 429) {
      errors.general = 'Demasiados intentos. Por favor espere y vuelva a intentar.'
    } else if (status === 423) {
      errors.general = 'Cuenta bloqueada temporalmente por múltiples intentos fallidos.'
    } else {
      errors.general = e?.response?.data?.message ?? 'Error al iniciar sesión'
    }
  } finally {
    loading.value = false
  }
}
</script>
