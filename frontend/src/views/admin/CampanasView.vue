<template>
  <div class="space-y-4">
    <div class="flex justify-end">
      <button v-if="auth.hasPermission('campana.administrar')" class="btn-primary btn-sm" @click="abrir(null)">
        <PlusIcon class="w-4 h-4" /> Nueva campaña
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-12"><LoadingSpinner /></div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="c in campanas" :key="c.id" class="card p-4 space-y-3">
        <div class="flex items-start justify-between">
          <div>
            <p class="font-semibold text-gray-900">{{ c.nombre }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ c.descripcion }}</p>
          </div>
          <span :class="c.activa ? 'badge-green' : 'badge-gray'" class="badge flex-shrink-0">
            {{ c.activa ? 'Activa' : 'Inactiva' }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-2 text-center">
          <div class="bg-gray-50 rounded p-2">
            <p class="text-lg font-bold text-gray-900">{{ c.candidatos_count }}</p>
            <p class="text-xs text-gray-500">Candidatos</p>
          </div>
          <div class="bg-gray-50 rounded p-2">
            <p class="text-lg font-bold text-gray-900">{{ (c.simpatizantes_count ?? 0).toLocaleString('es-CO') }}</p>
            <p class="text-xs text-gray-500">Simpatizantes</p>
          </div>
        </div>

        <div class="flex gap-2">
          <button class="btn-secondary btn-sm flex-1" @click="abrir(c)">Editar</button>
          <button v-if="auth.hasPermission('campana.administrar')" class="btn-ghost btn-sm text-red-500"
            @click="confirmar(c)">
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div v-if="!campanas.length" class="col-span-full py-10 text-center text-sm text-gray-400">
        Sin campañas registradas
      </div>
    </div>

    <!-- Modal -->
    <CampanaModal v-model="showModal" :campana="editando" @saved="cargar" />

    <ConfirmDialog v-model="showConfirm" title="Eliminar campaña"
      :message="`¿Eliminar la campaña '${eliminando?.nombre}'?`"
      variant="danger" confirm-label="Eliminar" @confirm="eliminar" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { campanaService }  from '@/services/adminService'
import { useAuthStore }    from '@/stores/auth'
import { useToastStore }   from '@/stores/toast'
import LoadingSpinner  from '@/components/ui/LoadingSpinner.vue'
import ConfirmDialog   from '@/components/ui/ConfirmDialog.vue'
import CampanaModal    from '@/components/admin/CampanaModal.vue'

const auth   = useAuthStore()
const toasts = useToastStore()

const campanas   = ref([])
const loading    = ref(false)
const showModal  = ref(false)
const showConfirm = ref(false)
const editando   = ref(null)
const eliminando = ref(null)

async function cargar() {
  loading.value = true
  try {
    const { data } = await campanaService.list()
    campanas.value = data.data ?? []
  } catch { toasts.error('Error al cargar campañas') }
  finally { loading.value = false }
}

function abrir(c)    { editando.value = c; showModal.value = true }
function confirmar(c){ eliminando.value = c; showConfirm.value = true }

async function eliminar() {
  try {
    await campanaService.delete(eliminando.value.id)
    toasts.success('Campaña eliminada')
    cargar()
  } catch (e) { toasts.error(e?.response?.data?.message ?? 'Error al eliminar') }
}

onMounted(cargar)
</script>
