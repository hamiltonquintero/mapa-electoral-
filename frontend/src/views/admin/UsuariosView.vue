<template>
  <div class="space-y-4">
    <!-- Acciones -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
      <div class="flex gap-2">
        <input v-model="filtros.buscar" type="search" class="input text-sm w-56" placeholder="Buscar nombre o correo…"
          @keyup.enter="cargar(1)" />
        <select v-model="filtros.rol" class="input text-sm w-44" @change="cargar(1)">
          <option value="">Todos los roles</option>
          <option v-for="r in rolesDisponibles" :key="r.name" :value="r.name">{{ r.name }}</option>
        </select>
      </div>
      <button v-if="auth.hasPermission('usuario.crear')" class="btn-primary btn-sm" @click="abrirCrear">
        <PlusIcon class="w-4 h-4" /> Nuevo usuario
      </button>
    </div>

    <!-- Tabla -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="flex justify-center py-12"><LoadingSpinner /></div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 bg-gray-50">
            <th class="text-left px-4 py-3 font-medium text-gray-600">Usuario</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Rol</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Territorios</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">2FA</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="u in usuarios" :key="u.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <UserAvatar :name="u.name" size="sm" />
                <div>
                  <p class="font-medium text-gray-900">{{ u.name }}</p>
                  <p class="text-xs text-gray-500">{{ u.email }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <span class="badge badge-blue">{{ u.roles?.[0]?.name ?? '—' }}</span>
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs hidden lg:table-cell">
              {{ u.territorios_count ?? 0 }} asignado(s)
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
              <span :class="u.two_factor_enabled ? 'text-green-600' : 'text-gray-400'" class="text-xs font-medium">
                {{ u.two_factor_enabled ? '✓ Activo' : '✗ Inactivo' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="u.activo ? 'badge-green' : 'badge-red'" class="badge">
                {{ u.activo ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <button class="btn-ghost btn-sm p-1" title="Editar" @click="abrirEditar(u)">
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button
                  v-if="u.id !== auth.user?.id"
                  class="btn-ghost btn-sm p-1 text-red-500 hover:text-red-700"
                  title="Eliminar"
                  @click="confirmarEliminar(u)"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!usuarios.length">
            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">Sin usuarios</td>
          </tr>
        </tbody>
      </table>
      <div v-if="pagination?.last_page > 1" class="px-4 py-3 border-t border-gray-100">
        <Pagination :pagination="pagination" @page="cargar" />
      </div>
    </div>

    <!-- Modal crear/editar -->
    <UsuarioModal
      v-model="showModal"
      :usuario="usuarioEditar"
      :roles="rolesDisponibles"
      @saved="cargar(1)"
    />

    <!-- Confirmar eliminar -->
    <ConfirmDialog
      v-model="showConfirm"
      title="Eliminar usuario"
      :message="`¿Eliminar a ${usuarioEliminar?.name}? Esta acción no se puede deshacer.`"
      variant="danger"
      confirm-label="Eliminar"
      @confirm="eliminar"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { usuarioService } from '@/services/adminService'
import { useAuthStore }   from '@/stores/auth'
import { useToastStore }  from '@/stores/toast'
import LoadingSpinner  from '@/components/ui/LoadingSpinner.vue'
import Pagination      from '@/components/ui/Pagination.vue'
import UserAvatar      from '@/components/ui/UserAvatar.vue'
import ConfirmDialog   from '@/components/ui/ConfirmDialog.vue'
import UsuarioModal    from '@/components/admin/UsuarioModal.vue'

const auth   = useAuthStore()
const toasts = useToastStore()

const usuarios          = ref([])
const pagination        = ref(null)
const loading           = ref(false)
const rolesDisponibles  = ref([])
const showModal         = ref(false)
const showConfirm       = ref(false)
const usuarioEditar     = ref(null)
const usuarioEliminar   = ref(null)

const filtros = reactive({ buscar: '', rol: '' })

async function cargar(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 20 }
    if (filtros.buscar) params.buscar = filtros.buscar
    if (filtros.rol)    params.rol    = filtros.rol
    const { data } = await usuarioService.list(params)
    usuarios.value  = data.data ?? []
    pagination.value = data.pagination ?? null
  } catch { toasts.error('Error al cargar usuarios') }
  finally { loading.value = false }
}

async function cargarRoles() {
  try {
    const { data } = await usuarioService.roles()
    rolesDisponibles.value = data.data ?? []
  } catch {}
}

function abrirCrear()    { usuarioEditar.value = null; showModal.value = true }
function abrirEditar(u)  { usuarioEditar.value = u;    showModal.value = true }

function confirmarEliminar(u) { usuarioEliminar.value = u; showConfirm.value = true }

async function eliminar() {
  if (!usuarioEliminar.value) return
  try {
    await usuarioService.delete(usuarioEliminar.value.id)
    toasts.success('Usuario eliminado')
    cargar(1)
  } catch (e) {
    toasts.error(e?.response?.data?.message ?? 'Error al eliminar')
  }
}

onMounted(() => { cargar(); cargarRoles() })
</script>
