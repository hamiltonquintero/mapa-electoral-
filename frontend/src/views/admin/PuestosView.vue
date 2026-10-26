<template>
  <div class="space-y-4">
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
      <div class="flex gap-2">
        <input v-model="filtros.buscar" type="search" class="input text-sm w-48" placeholder="Buscar puesto…"
          @keyup.enter="cargar(1)" />
        <button class="btn-primary btn-sm" @click="cargar(1)">Buscar</button>
      </div>
      <button v-if="auth.hasPermission('catalogo.administrar')" class="btn-primary btn-sm" @click="abrir(null)">
        <PlusIcon class="w-4 h-4" /> Nuevo puesto
      </button>
    </div>

    <div class="card overflow-hidden">
      <div v-if="loading" class="flex justify-center py-12"><LoadingSpinner /></div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th class="text-left px-4 py-3 font-medium text-gray-600">Nombre</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Municipio/Zona</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Mesas</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Simpatizantes</th>
              <th class="px-4 py-3" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in puestos" :key="p.id" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <p class="font-medium text-gray-900">{{ p.nombre }}</p>
                <p v-if="p.direccion" class="text-xs text-gray-500 truncate max-w-xs">{{ p.direccion }}</p>
              </td>
              <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ p.territorio?.nombre ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-700 font-medium">{{ p.total_mesas }}</td>
              <td class="px-4 py-3 text-gray-700 hidden lg:table-cell">
                {{ (p.simpatizantes_count ?? 0).toLocaleString('es-CO') }}
              </td>
              <td class="px-4 py-3 text-right">
                <button class="btn-ghost btn-sm p-1" @click="abrir(p)"><PencilIcon class="w-4 h-4" /></button>
                <button class="btn-ghost btn-sm p-1 text-red-500" @click="confirmar(p)"><TrashIcon class="w-4 h-4" /></button>
              </td>
            </tr>
            <tr v-if="!puestos.length">
              <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">Sin puestos registrados</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination?.last_page > 1" class="px-4 py-3 border-t border-gray-100">
        <Pagination :pagination="pagination" @page="cargar" />
      </div>
    </div>

    <PuestoModal v-model="showModal" :puesto="editando" @saved="cargar(1)" />
    <ConfirmDialog v-model="showConfirm" title="Eliminar puesto"
      :message="`¿Eliminar '${eliminando?.nombre}'?`"
      variant="danger" confirm-label="Eliminar" @confirm="eliminar" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { puestoService }  from '@/services/adminService'
import { useAuthStore }   from '@/stores/auth'
import { useToastStore }  from '@/stores/toast'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import Pagination     from '@/components/ui/Pagination.vue'
import ConfirmDialog  from '@/components/ui/ConfirmDialog.vue'
import PuestoModal    from '@/components/admin/PuestoModal.vue'

const auth   = useAuthStore()
const toasts = useToastStore()
const puestos     = ref([])
const pagination  = ref(null)
const loading     = ref(false)
const showModal   = ref(false)
const showConfirm = ref(false)
const editando    = ref(null)
const eliminando  = ref(null)
const filtros     = reactive({ buscar: '' })

async function cargar(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 25 }
    if (filtros.buscar) params.buscar = filtros.buscar
    const { data } = await puestoService.list(params)
    puestos.value    = data.data ?? []
    pagination.value = data.pagination ?? null
  } catch { toasts.error('Error al cargar puestos') }
  finally { loading.value = false }
}

function abrir(p)    { editando.value = p;  showModal.value = true }
function confirmar(p){ eliminando.value = p; showConfirm.value = true }

async function eliminar() {
  try {
    await puestoService.delete(eliminando.value.id)
    toasts.success('Puesto eliminado')
    cargar(1)
  } catch (e) { toasts.error(e?.response?.data?.message ?? 'Error') }
}

onMounted(() => cargar())
</script>
