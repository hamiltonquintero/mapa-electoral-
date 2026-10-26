<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <select v-model="filtros.campana_id" class="input text-sm w-56" @change="cargar">
        <option value="">Todas las campañas</option>
        <option v-for="c in campanas" :key="c.id" :value="c.id">{{ c.nombre }}</option>
      </select>
      <button v-if="auth.hasPermission('campana.administrar')" class="btn-primary btn-sm" @click="abrir(null)">
        <PlusIcon class="w-4 h-4" /> Nuevo candidato
      </button>
    </div>

    <div class="card overflow-hidden">
      <div v-if="loading" class="flex justify-center py-12"><LoadingSpinner /></div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 bg-gray-50">
            <th class="text-left px-4 py-3 font-medium text-gray-600">Nombre</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Cargo</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Campaña</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Simpatizantes</th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="c in candidatos" :key="c.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: c.color ?? '#6b7280' }" />
                <span class="font-medium text-gray-900">{{ c.nombre }}</span>
              </div>
            </td>
            <td class="px-4 py-3">
              <span class="badge badge-blue capitalize">{{ c.cargo }}</span>
            </td>
            <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ c.campana?.nombre }}</td>
            <td class="px-4 py-3 text-gray-700 hidden md:table-cell">
              {{ (c.simpatizantes_count ?? 0).toLocaleString('es-CO') }}
            </td>
            <td class="px-4 py-3 text-right">
              <button class="btn-ghost btn-sm p-1" @click="abrir(c)"><PencilIcon class="w-4 h-4" /></button>
              <button class="btn-ghost btn-sm p-1 text-red-500" @click="confirmar(c)"><TrashIcon class="w-4 h-4" /></button>
            </td>
          </tr>
          <tr v-if="!candidatos.length">
            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">Sin candidatos</td>
          </tr>
        </tbody>
      </table>
    </div>

    <CandidatoModal v-model="showModal" :candidato="editando" :campanas="campanas" @saved="cargar" />
    <ConfirmDialog v-model="showConfirm" title="Eliminar candidato"
      :message="`¿Eliminar a ${eliminando?.nombre}?`"
      variant="danger" confirm-label="Eliminar" @confirm="eliminar" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { candidatoService, campanaService } from '@/services/adminService'
import { useAuthStore }   from '@/stores/auth'
import { useToastStore }  from '@/stores/toast'
import LoadingSpinner  from '@/components/ui/LoadingSpinner.vue'
import ConfirmDialog   from '@/components/ui/ConfirmDialog.vue'
import CandidatoModal  from '@/components/admin/CandidatoModal.vue'

const auth   = useAuthStore()
const toasts = useToastStore()

const candidatos  = ref([])
const campanas    = ref([])
const loading     = ref(false)
const showModal   = ref(false)
const showConfirm = ref(false)
const editando    = ref(null)
const eliminando  = ref(null)
const filtros     = reactive({ campana_id: '' })

async function cargar() {
  loading.value = true
  try {
    const params = {}
    if (filtros.campana_id) params.campana_id = filtros.campana_id
    const { data } = await candidatoService.list(params)
    candidatos.value = data.data ?? []
  } catch { toasts.error('Error al cargar candidatos') }
  finally { loading.value = false }
}

async function cargarCampanas() {
  try {
    const { data } = await campanaService.list()
    campanas.value = data.data ?? []
  } catch {}
}

function abrir(c)    { editando.value = c;  showModal.value = true }
function confirmar(c){ eliminando.value = c; showConfirm.value = true }

async function eliminar() {
  try {
    await candidatoService.delete(eliminando.value.id)
    toasts.success('Candidato eliminado')
    cargar()
  } catch (e) { toasts.error(e?.response?.data?.message ?? 'Error') }
}

onMounted(() => { cargar(); cargarCampanas() })
</script>
