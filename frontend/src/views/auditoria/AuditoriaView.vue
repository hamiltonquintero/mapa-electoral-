<template>
  <div class="p-6 max-w-7xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Auditoría del sistema</h2>
      <button
        v-if="auth.hasPermission('auditoria.exportar')"
        class="btn-secondary btn-sm"
        @click="showExportModal = true"
      >
        <ArrowDownTrayIcon class="w-4 h-4" /> Exportar CSV
      </button>
    </div>

    <!-- Alertas de seguridad -->
    <div v-if="alertas.length" class="space-y-2">
      <div
        v-for="alerta in alertas"
        :key="alerta.tipo"
        class="rounded-lg border p-3 flex items-start gap-3"
        :class="alerta.severidad === 'alta'
          ? 'bg-red-50 border-red-200'
          : 'bg-yellow-50 border-yellow-200'"
      >
        <ExclamationTriangleIcon
          class="w-5 h-5 flex-shrink-0"
          :class="alerta.severidad === 'alta' ? 'text-red-600' : 'text-yellow-600'"
        />
        <p class="text-sm" :class="alerta.severidad === 'alta' ? 'text-red-800' : 'text-yellow-800'">
          {{ alerta.mensaje }}
        </p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="card p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
          <label class="label text-xs">Acción</label>
          <select v-model="filtros.accion" class="input text-sm" @change="cargar(1)">
            <option value="">Todas</option>
            <option v-for="a in accionesDisponibles" :key="a.accion" :value="a.accion">
              {{ a.accion }} ({{ a.total }})
            </option>
          </select>
        </div>
        <div>
          <label class="label text-xs">Desde</label>
          <input v-model="filtros.desde" type="datetime-local" class="input text-sm" @change="cargar(1)" />
        </div>
        <div>
          <label class="label text-xs">Hasta</label>
          <input v-model="filtros.hasta" type="datetime-local" class="input text-sm" @change="cargar(1)" />
        </div>
        <div>
          <label class="label text-xs">Buscar</label>
          <input v-model="filtros.buscar" type="search" class="input text-sm" placeholder="En acción o detalle…" @keyup.enter="cargar(1)" />
        </div>
        <div class="flex items-end gap-2">
          <button class="btn-primary btn-sm flex-1" @click="cargar(1)">Buscar</button>
          <button class="btn-secondary btn-sm" @click="resetFiltros">✕</button>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="flex justify-center py-16">
        <LoadingSpinner label="Cargando logs…" />
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th class="text-left px-4 py-2.5 font-medium text-gray-600 w-36">Fecha/Hora</th>
              <th class="text-left px-4 py-2.5 font-medium text-gray-600">Usuario</th>
              <th class="text-left px-4 py-2.5 font-medium text-gray-600">Acción</th>
              <th class="text-left px-4 py-2.5 font-medium text-gray-600 hidden md:table-cell">Entidad</th>
              <th class="text-left px-4 py-2.5 font-medium text-gray-600 hidden lg:table-cell">IP</th>
              <th class="px-4 py-2.5" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="evento in eventos"
              :key="evento.id"
              class="hover:bg-gray-50 transition cursor-pointer"
              @click="eventoSeleccionado = evento"
            >
              <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">
                {{ formatDate(evento.creado_en) }}
              </td>
              <td class="px-4 py-2.5">
                <p class="font-medium text-gray-900">{{ evento.user?.name ?? 'Sistema' }}</p>
                <p class="text-gray-400">{{ evento.rol }}</p>
              </td>
              <td class="px-4 py-2.5">
                <AccionBadge :accion="evento.accion" />
              </td>
              <td class="px-4 py-2.5 text-gray-500 hidden md:table-cell">
                {{ evento.entidad }} <span v-if="evento.entidad_id" class="text-gray-400 font-mono">·{{ evento.entidad_id.slice(0, 8) }}</span>
              </td>
              <td class="px-4 py-2.5 font-mono text-gray-400 hidden lg:table-cell">
                {{ evento.ip }}
              </td>
              <td class="px-4 py-2.5">
                <ChevronRightIcon class="w-3.5 h-3.5 text-gray-400" />
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!eventos.length" class="py-12 text-center text-sm text-gray-400">
          Sin eventos de auditoría para los filtros seleccionados
        </div>
      </div>

      <div v-if="pagination?.last_page > 1" class="px-4 py-3 border-t border-gray-100">
        <Pagination :pagination="pagination" @page="cargar" />
      </div>
    </div>

    <!-- Drawer detalle evento -->
    <Transition name="slide">
      <div
        v-if="eventoSeleccionado"
        class="fixed inset-y-0 right-0 z-40 w-96 bg-white border-l border-gray-200 shadow-2xl flex flex-col"
      >
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
          <h3 class="text-sm font-semibold text-gray-900">Detalle del evento</h3>
          <button class="btn-ghost p-1" @click="eventoSeleccionado = null">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-thin">
          <DetalleField label="ID"       :value="eventoSeleccionado.id"              small />
          <DetalleField label="Fecha"    :value="formatDate(eventoSeleccionado.creado_en)" small />
          <DetalleField label="Usuario"  :value="eventoSeleccionado.user?.name ?? 'Sistema'" small />
          <DetalleField label="Rol"      :value="eventoSeleccionado.rol ?? '—'"      small />
          <DetalleField label="Acción"   :value="eventoSeleccionado.accion"          small />
          <DetalleField label="Entidad"  :value="eventoSeleccionado.entidad ?? '—'"  small />
          <DetalleField label="Entidad ID" :value="eventoSeleccionado.entidad_id ?? '—'" small />
          <DetalleField label="IP"       :value="eventoSeleccionado.ip ?? '—'"       small />
          <div v-if="eventoSeleccionado.detalle">
            <p class="text-xs text-gray-500 mb-1">Detalle JSON</p>
            <pre class="text-xs bg-gray-50 rounded p-2 overflow-auto max-h-48 border border-gray-100">{{ JSON.stringify(eventoSeleccionado.detalle, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </Transition>
    <div v-if="eventoSeleccionado" class="fixed inset-0 z-30 bg-black/20" @click="eventoSeleccionado = null" />

    <!-- Modal exportar -->
    <ExportarAuditoriaModal
      v-model="showExportModal"
      :filtros="filtros"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  ArrowDownTrayIcon, ChevronRightIcon, XMarkIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import api            from '@/services/api'
import { useAuthStore }  from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import LoadingSpinner    from '@/components/ui/LoadingSpinner.vue'
import Pagination        from '@/components/ui/Pagination.vue'
import AccionBadge       from '@/components/auditoria/AccionBadge.vue'
import ExportarAuditoriaModal from '@/components/auditoria/ExportarAuditoriaModal.vue'
import DetalleField      from '@/components/simpatizantes/DetalleField.vue'
import dayjs             from 'dayjs'

const auth   = useAuthStore()
const toasts = useToastStore()

const eventos            = ref([])
const pagination         = ref(null)
const loading            = ref(false)
const eventoSeleccionado = ref(null)
const showExportModal    = ref(false)
const alertas            = ref([])
const accionesDisponibles = ref([])

const filtros = reactive({
  accion: '', desde: '', hasta: '', buscar: '', per_page: 50,
})

function formatDate(d) { return d ? dayjs(d).format('DD/MM/YYYY HH:mm') : '—' }

async function cargar(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (filtros.accion) params.accion = filtros.accion
    if (filtros.desde)  params.desde  = filtros.desde
    if (filtros.hasta)  params.hasta  = filtros.hasta
    if (filtros.buscar) params.buscar = filtros.buscar
    params.per_page = filtros.per_page

    const { data } = await api.get('/auditoria', { params })
    eventos.value    = data.data ?? []
    pagination.value = data.pagination ?? null
  } catch { toasts.error('Error al cargar la auditoría') }
  finally  { loading.value = false }
}

async function cargarAcciones() {
  try {
    const { data } = await api.get('/auditoria/acciones')
    accionesDisponibles.value = data.data ?? []
  } catch {}
}

async function cargarAlertas() {
  try {
    const { data } = await api.get('/auditoria/alertas', { params: { horas: 24 } })
    alertas.value = (data.data?.alertas ?? []).filter((a) => a.severidad === 'alta')
  } catch {}
}

function resetFiltros() {
  Object.assign(filtros, { accion: '', desde: '', hasta: '', buscar: '' })
  cargar(1)
}

onMounted(() => {
  cargar()
  cargarAcciones()
  cargarAlertas()
})
</script>
