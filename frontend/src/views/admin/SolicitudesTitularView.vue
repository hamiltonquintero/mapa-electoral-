<template>
  <div class="p-6 max-w-5xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Solicitudes de titulares</h2>
        <p class="text-sm text-gray-500">Derechos ARCO — Ley 1581 de 2012</p>
      </div>
      <button class="btn-primary btn-sm" @click="showCrear = true">
        <PlusIcon class="w-4 h-4" /> Nueva solicitud
      </button>
    </div>

    <!-- Alerta de solicitudes vencidas -->
    <div v-if="alertasVencidas > 0"
      class="rounded-lg bg-red-50 border border-red-200 p-3 flex items-center gap-3">
      <ExclamationTriangleIcon class="w-5 h-5 text-red-600 flex-shrink-0" />
      <p class="text-sm text-red-800">
        <strong>{{ alertasVencidas }} solicitud(es)</strong> han superado el plazo legal de 15 días hábiles.
      </p>
    </div>

    <!-- Filtros -->
    <div class="card p-4 flex flex-wrap gap-3">
      <select v-model="filtros.tipo" class="input text-sm w-40" @change="cargar(1)">
        <option value="">Todos los tipos</option>
        <option value="consulta">Consulta</option>
        <option value="rectificacion">Rectificación</option>
        <option value="supresion">Supresión</option>
      </select>
      <select v-model="filtros.estado" class="input text-sm w-40" @change="cargar(1)">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="en_proceso">En proceso</option>
        <option value="resuelta">Resuelta</option>
        <option value="rechazada">Rechazada</option>
      </select>
    </div>

    <!-- Tabla -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="flex justify-center py-12"><LoadingSpinner /></div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200 bg-gray-50">
            <th class="text-left px-4 py-3 font-medium text-gray-600">Tipo</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Persona</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Recibida</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Límite</th>
            <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="s in solicitudes" :key="s.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <span class="badge" :class="tipoBadge(s.tipo)">{{ s.tipo }}</span>
            </td>
            <td class="px-4 py-3 text-gray-700">
              {{ s.simpatizante ? `${s.simpatizante.nombres} ${s.simpatizante.apellidos}` : '—' }}
            </td>
            <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(s.fecha_recepcion) }}</td>
            <td class="px-4 py-3 text-xs font-medium"
              :class="esVencida(s) ? 'text-red-600' : 'text-gray-700'">
              {{ formatDate(s.fecha_limite) }}
              <span v-if="esVencida(s)" class="ml-1">⚠</span>
            </td>
            <td class="px-4 py-3">
              <span class="badge" :class="estadoBadge(s.estado)">{{ s.estado }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <button class="btn-ghost btn-sm p-1" @click="seleccionar(s)">
                <PencilIcon class="w-4 h-4" />
              </button>
            </td>
          </tr>
          <tr v-if="!solicitudes.length">
            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">Sin solicitudes</td>
          </tr>
        </tbody>
      </table>
      <div v-if="pagination?.last_page > 1" class="px-4 py-3 border-t border-gray-100">
        <Pagination :pagination="pagination" @page="cargar" />
      </div>
    </div>

    <!-- Modal actualizar estado -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="seleccionada" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="seleccionada = null" />
          <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <h3 class="font-semibold text-gray-900">Actualizar solicitud</h3>
            <div class="space-y-3">
              <div>
                <label class="label text-xs">Tipo</label>
                <p class="text-sm font-medium text-gray-900 capitalize">{{ seleccionada.tipo }}</p>
              </div>
              <FormField label="Nuevo estado">
                <select v-model="actualizacion.estado" class="input text-sm">
                  <option value="en_proceso">En proceso</option>
                  <option value="resuelta">Resuelta</option>
                  <option value="rechazada">Rechazada</option>
                </select>
              </FormField>
              <FormField label="Notas">
                <textarea v-model="actualizacion.notas" rows="3" class="input resize-none text-sm"
                  placeholder="Descripción de la resolución…" />
              </FormField>
              <div v-if="seleccionada.tipo === 'supresion' && actualizacion.estado === 'resuelta'"
                class="rounded-lg bg-red-50 border border-red-200 p-3">
                <p class="text-xs text-red-800">
                  ⚠ Al marcar como resuelta, se eliminará permanentemente la información personal de este registro.
                </p>
              </div>
            </div>
            <div class="flex justify-end gap-3">
              <button class="btn-secondary" @click="seleccionada = null">Cancelar</button>
              <button :class="actualizacion.estado === 'resuelta' && seleccionada.tipo === 'supresion'
                ? 'btn-danger' : 'btn-primary'" @click="actualizar" :disabled="saving">
                <LoadingSpinner v-if="saving" size="sm" />Guardar
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Modal crear -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCrear" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40" @click="showCrear = false" />
          <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <h3 class="font-semibold text-gray-900">Nueva solicitud de titular</h3>
            <FormField label="Tipo de solicitud *">
              <select v-model="nuevaSolicitud.tipo" class="input text-sm">
                <option value="">Selecciona tipo</option>
                <option value="consulta">Consulta (acceso a datos)</option>
                <option value="rectificacion">Rectificación</option>
                <option value="supresion">Supresión (derecho al olvido)</option>
              </select>
            </FormField>
            <FormField label="ID del simpatizante (opcional)">
              <input v-model="nuevaSolicitud.simpatizante_id" type="text" class="input text-sm font-mono"
                placeholder="UUID del simpatizante" />
            </FormField>
            <FormField label="Notas">
              <textarea v-model="nuevaSolicitud.notas" rows="2" class="input resize-none text-sm" />
            </FormField>
            <div class="flex justify-end gap-3">
              <button class="btn-secondary" @click="showCrear = false">Cancelar</button>
              <button class="btn-primary" @click="crear" :disabled="!nuevaSolicitud.tipo || saving">
                <LoadingSpinner v-if="saving" size="sm" />Registrar
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { PlusIcon, PencilIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import api            from '@/services/api'
import { useToastStore } from '@/stores/toast'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import Pagination     from '@/components/ui/Pagination.vue'
import FormField      from '@/components/ui/FormField.vue'
import dayjs          from 'dayjs'

const toasts      = useToastStore()
const solicitudes = ref([])
const pagination  = ref(null)
const loading     = ref(false)
const saving      = ref(false)
const alertasVencidas = ref(0)
const seleccionada = ref(null)
const showCrear   = ref(false)
const filtros     = reactive({ tipo: '', estado: '' })
const actualizacion = reactive({ estado: 'en_proceso', notas: '' })
const nuevaSolicitud = reactive({ tipo: '', simpatizante_id: '', notas: '' })

const formatDate = (d) => d ? dayjs(d).format('DD/MM/YYYY') : '—'
const esVencida  = (s) => s.estado === 'pendiente' && dayjs().isAfter(dayjs(s.fecha_limite))

const tipoBadge = (t) => ({
  consulta:      'badge-blue',
  rectificacion: 'badge-yellow',
  supresion:     'badge-red',
}[t] ?? 'badge-gray')

const estadoBadge = (e) => ({
  pendiente:  'badge-yellow',
  en_proceso: 'badge-blue',
  resuelta:   'badge-green',
  rechazada:  'badge-red',
}[e] ?? 'badge-gray')

function seleccionar(s) {
  seleccionada.value = s
  actualizacion.estado = 'en_proceso'
  actualizacion.notas = s.notas ?? ''
}

async function cargar(page = 1) {
  loading.value = true
  try {
    const params = { page }
    if (filtros.tipo)   params.tipo   = filtros.tipo
    if (filtros.estado) params.estado = filtros.estado
    const { data } = await api.get('/solicitudes-titulares', { params })
    solicitudes.value   = data.data ?? []
    pagination.value    = data.pagination ?? null
    alertasVencidas.value = data.alertas_vencidas ?? 0
  } catch { toasts.error('Error al cargar solicitudes') }
  finally { loading.value = false }
}

async function actualizar() {
  if (!seleccionada.value || saving.value) return
  saving.value = true
  try {
    await api.put(`/solicitudes-titulares/${seleccionada.value.id}`, {
      estado: actualizacion.estado,
      notas:  actualizacion.notas,
    })
    toasts.success('Solicitud actualizada')
    seleccionada.value = null
    cargar(1)
  } catch (e) {
    toasts.error(e?.response?.data?.message ?? 'Error')
  } finally { saving.value = false }
}

async function crear() {
  if (!nuevaSolicitud.tipo || saving.value) return
  saving.value = true
  try {
    await api.post('/solicitudes-titulares', nuevaSolicitud)
    toasts.success('Solicitud registrada. Plazo: 15 días hábiles')
    showCrear.value = false
    Object.assign(nuevaSolicitud, { tipo: '', simpatizante_id: '', notas: '' })
    cargar(1)
  } catch (e) {
    toasts.error(e?.response?.data?.message ?? 'Error')
  } finally { saving.value = false }
}

onMounted(cargar)
</script>
