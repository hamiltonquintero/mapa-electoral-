<template>
  <div class="p-6 max-w-2xl mx-auto space-y-5">
    <h2 class="text-lg font-semibold text-gray-900">Mi perfil</h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
      <!-- Tarjeta de usuario -->
      <div class="sm:col-span-1">
        <div class="card p-5 flex flex-col items-center text-center gap-3">
          <UserAvatar :name="auth.user?.name" size="lg" />
          <div>
            <p class="font-semibold text-gray-900">{{ auth.user?.name }}</p>
            <p class="text-sm text-gray-500">{{ auth.user?.email }}</p>
          </div>
          <span class="badge badge-blue">{{ auth.roles[0] ?? 'Sin rol' }}</span>

          <button class="btn-danger btn-sm w-full mt-2" @click="cerrarSesion">
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
            Cerrar sesión
          </button>
        </div>
      </div>

      <!-- Detalle y 2FA -->
      <div class="sm:col-span-2 space-y-5">
        <!-- Información de cuenta -->
        <div class="card p-5 space-y-3">
          <h3 class="text-sm font-semibold text-gray-700">Información de cuenta</h3>
          <dl class="space-y-2">
            <DetalleField label="Nombre completo" :value="auth.user?.name" small />
            <DetalleField label="Correo electrónico" :value="auth.user?.email" small />
            <DetalleField label="Rol asignado" :value="auth.roles[0] ?? '—'" small />
            <DetalleField
              label="Territorios asignados"
              :value="auth.user?.territorios?.map(t => t.nombre).join(', ') || 'Acceso completo'"
              small
            />
          </dl>
        </div>

        <!-- Autenticación de dos factores -->
        <div class="card p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Autenticación de dos factores (2FA)</h3>
            <span
              class="badge"
              :class="status2fa?.enabled ? 'badge-green' : 'badge-yellow'"
            >
              {{ status2fa?.enabled ? 'Activo' : 'Inactivo' }}
            </span>
          </div>

          <p class="text-sm text-gray-500">
            El 2FA agrega una capa de seguridad adicional a tu cuenta usando Google Authenticator o Authy.
          </p>

          <!-- Configurar 2FA -->
          <template v-if="!status2fa?.enabled">
            <button class="btn-primary btn-sm" @click="setup2fa" :disabled="loading2fa">
              <LoadingSpinner v-if="loading2fa" size="sm" />
              Activar 2FA
            </button>

            <div v-if="qrCode" class="space-y-3">
              <p class="text-sm text-gray-600">Escanea este código QR con tu app de autenticación:</p>
              <div class="bg-white border border-gray-200 rounded-lg p-4 inline-block" v-html="qrCode" />
              <div>
                <label class="label text-xs">Código de verificación</label>
                <div class="flex gap-2">
                  <input v-model="codigoVerif" type="text" inputmode="numeric" maxlength="6"
                    class="input font-mono text-lg tracking-widest w-36" placeholder="000000" />
                  <button class="btn-primary btn-sm" @click="enable2fa" :disabled="!codigoVerif">Activar</button>
                </div>
              </div>
            </div>
          </template>

          <!-- Desactivar 2FA -->
          <template v-else>
            <div>
              <label class="label text-xs">Código para desactivar</label>
              <div class="flex gap-2">
                <input v-model="codigoDesact" type="text" inputmode="numeric" maxlength="6"
                  class="input font-mono w-36" placeholder="000000" />
                <button class="btn-danger btn-sm" @click="disable2fa" :disabled="!codigoDesact">Desactivar</button>
              </div>
            </div>
          </template>
        </div>

        <!-- Permisos -->
        <div class="card p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Permisos activos</h3>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="perm in auth.permissions"
              :key="perm"
              class="badge-gray text-xs px-2 py-0.5 rounded font-mono"
            >
              {{ perm }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore }  from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { authService }   from '@/services/authService'
import UserAvatar        from '@/components/ui/UserAvatar.vue'
import DetalleField      from '@/components/simpatizantes/DetalleField.vue'
import LoadingSpinner    from '@/components/ui/LoadingSpinner.vue'

const router = useRouter()
const auth   = useAuthStore()
const toasts = useToastStore()

const status2fa    = ref(null)
const qrCode       = ref('')
const codigoVerif  = ref('')
const codigoDesact = ref('')
const loading2fa   = ref(false)

async function cerrarSesion() {
  await auth.logout()
  router.push('/login')
}

async function cargarStatus2fa() {
  try {
    const { data } = await authService.status2fa()
    status2fa.value = data.data ?? data
  } catch {}
}

async function setup2fa() {
  loading2fa.value = true
  try {
    const { data } = await authService.setup2fa()
    qrCode.value = data.data?.qr_code_svg ?? data.qr_code_svg
  } catch {
    toasts.error('Error al configurar 2FA')
  } finally {
    loading2fa.value = false
  }
}

async function enable2fa() {
  try {
    await authService.enable2fa(codigoVerif.value)
    toasts.success('2FA activado correctamente')
    qrCode.value = ''
    codigoVerif.value = ''
    await cargarStatus2fa()
  } catch {
    toasts.error('Código incorrecto')
  }
}

async function disable2fa() {
  try {
    await authService.disable2fa(codigoDesact.value)
    toasts.success('2FA desactivado')
    codigoDesact.value = ''
    await cargarStatus2fa()
  } catch {
    toasts.error('Código incorrecto')
  }
}

onMounted(cargarStatus2fa)
</script>
