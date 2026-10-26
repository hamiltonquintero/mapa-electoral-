<template>
  <div class="min-h-full flex flex-col justify-center items-center bg-gray-50 p-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-600 shadow-lg mb-4">
          <ShieldCheckIcon class="w-8 h-8 text-white" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Verificación</h1>
        <p class="text-sm text-gray-500 mt-1">Ingresa el código de tu aplicación de autenticación</p>
      </div>

      <div class="card p-6">
        <form @submit.prevent="handleVerify" novalidate>
          <div>
            <label for="code" class="label">Código de 6 dígitos</label>
            <input
              id="code"
              v-model="code"
              type="text"
              inputmode="numeric"
              autocomplete="one-time-code"
              maxlength="6"
              class="input text-center text-xl tracking-widest font-mono"
              :class="{ 'input-error': error }"
              placeholder="000000"
              autofocus
            />
            <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
          </div>

          <p class="text-xs text-gray-500 mt-3">
            ¿No tienes acceso a tu app? Usa un
            <button type="button" class="text-primary-600 underline" @click="useRecovery = !useRecovery">
              código de recuperación
            </button>
          </p>

          <div v-if="useRecovery" class="mt-3">
            <label class="label">Código de recuperación</label>
            <input v-model="recoveryCode" type="text" class="input font-mono" placeholder="xxxx-xxxx-xxxx" />
          </div>

          <button type="submit" class="btn-primary w-full mt-5" :disabled="loading">
            <LoadingSpinner v-if="loading" size="sm" />
            <span>{{ loading ? 'Verificando…' : 'Verificar' }}</span>
          </button>

          <button type="button" class="btn-ghost w-full mt-2 text-sm" @click="$router.push('/login')">
            ← Volver al inicio de sesión
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ShieldCheckIcon } from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()

const code         = ref('')
const recoveryCode = ref('')
const useRecovery  = ref(false)
const loading      = ref(false)
const error        = ref('')

const tempToken = route.query.temp

async function handleVerify() {
  if (loading.value) return
  error.value = ''

  const c = useRecovery.value ? recoveryCode.value : code.value
  if (!c) { error.value = 'Ingresa el código'; return }

  loading.value = true
  try {
    await auth.verify2fa(c, tempToken)
    const redirect = route.query.redirect
    router.push(redirect ? String(redirect) : '/mapa')
  } catch (e) {
    error.value = e?.response?.data?.message ?? 'Código incorrecto'
  } finally {
    loading.value = false
  }
}
</script>
