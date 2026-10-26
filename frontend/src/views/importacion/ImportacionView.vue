<template>
  <div class="p-6 max-w-4xl mx-auto space-y-6">
    <h2 class="text-lg font-semibold text-gray-900">Importación masiva</h2>

    <!-- Asistente por pasos -->
    <div class="card overflow-hidden">
      <!-- Indicador de pasos -->
      <div class="flex border-b border-gray-200">
        <button
          v-for="(paso, i) in pasos"
          :key="i"
          class="flex-1 py-3 text-xs font-medium transition text-center border-b-2"
          :class="step === i
            ? 'border-primary-600 text-primary-700'
            : step > i
              ? 'border-green-500 text-green-600'
              : 'border-transparent text-gray-400 cursor-default'"
          :disabled="step < i"
          @click="step > i && (step = i)"
        >
          <span class="block text-lg leading-tight">{{ paso.icon }}</span>
          {{ paso.label }}
        </button>
      </div>

      <!-- Paso 1: Cargar archivo -->
      <div v-if="step === 0" class="p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">1. Cargar archivo CSV o Excel</h3>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-blue-50 border border-blue-200 rounded-xl p-4">
          <div>
            <p class="text-sm font-bold text-blue-900">📥 Plantilla oficial de simpatizantes</p>
            <p class="text-xs text-blue-700">Descarga la plantilla con las columnas esperadas para importar tus bases de datos.</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs flex items-center gap-1.5 transition cursor-pointer"
              @click="descargarPlantilla('xlsx')"
            >
              <span>📊 Excel (.xlsx)</span>
            </button>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 text-xs font-bold shadow-xs flex items-center gap-1.5 transition cursor-pointer"
              @click="descargarPlantilla('csv')"
            >
              <span>📄 CSV (.csv)</span>
            </button>
          </div>
        </div>

        <div
          class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-primary-400 transition"
          :class="{ 'border-primary-500 bg-primary-50': dragOver }"
          @dragover.prevent="dragOver = true"
          @dragleave="dragOver = false"
          @drop.prevent="onDrop"
          @click="fileInput?.click()"
        >
          <input ref="fileInput" type="file" class="hidden" accept=".csv,.xlsx,.xls" @change="onFileChange" />
          <template v-if="archivo">
            <DocumentTextIcon class="w-10 h-10 mx-auto text-primary-500 mb-2" />
            <p class="font-medium text-gray-900">{{ archivo.name }}</p>
            <p class="text-sm text-gray-500">{{ (archivo.size / 1024).toFixed(1) }} KB</p>
            <button type="button" class="text-xs text-red-600 mt-2 hover:underline" @click.stop="archivo = null">
              Quitar archivo
            </button>
          </template>
          <template v-else>
            <ArrowUpTrayIcon class="w-10 h-10 mx-auto text-gray-400 mb-3" />
            <p class="text-sm text-gray-600">Arrastra el archivo aquí o haz clic para seleccionar</p>
            <p class="text-xs text-gray-400 mt-1">CSV o Excel (.csv, .xlsx) · Máx 10 MB</p>
          </template>
        </div>

        <div class="flex justify-end">
          <button class="btn-primary" :disabled="!archivo || validating" @click="validarArchivo">
            <LoadingSpinner v-if="validating" size="sm" />
            {{ validating ? 'Validando…' : 'Validar archivo →' }}
          </button>
        </div>
      </div>

      <!-- Paso 2: Resultados de validación -->
      <div v-if="step === 1" class="p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">2. Revisión de validación</h3>

        <div class="grid grid-cols-3 gap-4">
          <div class="stat-card text-center">
            <p class="text-2xl font-bold text-gray-900">{{ validacion?.filas_total ?? 0 }}</p>
            <p class="text-xs text-gray-500">Total filas</p>
          </div>
          <div class="stat-card text-center">
            <p class="text-2xl font-bold text-green-700">{{ validacion?.filas_ok ?? 0 }}</p>
            <p class="text-xs text-gray-500">Correctas</p>
          </div>
          <div class="stat-card text-center">
            <p class="text-2xl font-bold text-red-700">{{ validacion?.filas_error ?? 0 }}</p>
            <p class="text-xs text-gray-500">Con errores</p>
          </div>
        </div>

        <!-- Tabla de errores -->
        <div v-if="validacion?.errores?.length" class="rounded-lg border border-red-200 overflow-hidden">
          <div class="bg-red-50 px-4 py-2 border-b border-red-200">
            <p class="text-xs font-semibold text-red-800">Errores encontrados</p>
          </div>
          <div class="max-h-48 overflow-y-auto">
            <table class="w-full text-xs">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                  <th class="text-left px-3 py-2 text-gray-600">Fila</th>
                  <th class="text-left px-3 py-2 text-gray-600">Campo</th>
                  <th class="text-left px-3 py-2 text-gray-600">Error</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(err, i) in validacion.errores.slice(0, 50)" :key="i" class="hover:bg-gray-50">
                  <td class="px-3 py-2 text-gray-500">{{ err.fila }}</td>
                  <td class="px-3 py-2 text-gray-700">{{ err.campo }}</td>
                  <td class="px-3 py-2 text-red-700">{{ err.mensaje }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Opciones de importación -->
        <div class="space-y-3">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="opciones.saltar_duplicados" type="checkbox" class="rounded border-gray-300 text-primary-600" />
            <span class="text-sm text-gray-700">Saltar duplicados automáticamente</span>
          </label>
          <FormField label="Nombre del lote (opcional)">
            <input v-model="opciones.nombre_lote" type="text" class="input text-sm" placeholder="Ej: Reunión barrio El Prado 20/10/2026" />
          </FormField>
        </div>

        <div class="flex justify-between">
          <button class="btn-secondary" @click="step = 0">← Atrás</button>
          <button
            class="btn-primary"
            :disabled="(validacion?.filas_ok ?? 0) === 0"
            @click="step = 2"
          >
            Continuar →
          </button>
        </div>
      </div>

      <!-- Paso 3: Confirmar e importar -->
      <div v-if="step === 2" class="p-6 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">3. Confirmar importación</h3>

        <div class="rounded-lg bg-blue-50 border border-blue-200 p-4">
          <p class="text-sm text-blue-800">
            Se importarán <strong>{{ validacion?.filas_ok }}</strong> registros.
            <span v-if="validacion?.filas_error > 0">
              Se ignorarán {{ validacion.filas_error }} filas con errores.
            </span>
          </p>
        </div>

        <div class="flex justify-between">
          <button class="btn-secondary" @click="step = 1">← Atrás</button>
          <button class="btn-primary" :disabled="importing" @click="procesarImportacion">
            <LoadingSpinner v-if="importing" size="sm" />
            {{ importing ? 'Importando…' : '✓ Importar ahora' }}
          </button>
        </div>
      </div>

      <!-- Paso 4: Resultado -->
      <div v-if="step === 3" class="p-6 text-center space-y-4">
        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto">
          <CheckCircleIcon class="w-10 h-10 text-green-600" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900">¡Importación completada!</h3>
        <p class="text-sm text-gray-600">
          Se importaron <strong>{{ resultado?.filas_ok }}</strong> registros exitosamente.
        </p>
        <div class="flex justify-center gap-3">
          <button class="btn-secondary" @click="resetear">Nueva importación</button>
          <RouterLink to="/simpatizantes" class="btn-primary">Ver simpatizantes</RouterLink>
        </div>
      </div>
    </div>

    <!-- Historial de importaciones -->
    <div class="card overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-700">Importaciones recientes</h3>
      </div>
      <div v-if="historial.length" class="divide-y divide-gray-100">
        <div v-for="imp in historial" :key="imp.id" class="flex items-center justify-between px-4 py-3">
          <div>
            <p class="text-sm font-medium text-gray-900">{{ imp.nombre_lote ?? 'Sin nombre' }}</p>
            <p class="text-xs text-gray-500">
              {{ imp.filas_ok }} registros · {{ formatDate(imp.created_at) }}
            </p>
          </div>
          <span class="badge" :class="imp.estado === 'completada' ? 'badge-green' : imp.estado === 'fallida' ? 'badge-red' : 'badge-yellow'">
            {{ imp.estado }}
          </span>
        </div>
      </div>
      <div v-else class="py-8 text-center text-sm text-gray-400">Sin importaciones previas</div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowUpTrayIcon, DocumentTextIcon, CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import api            from '@/services/api'
import { useToastStore } from '@/stores/toast'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import FormField      from '@/components/ui/FormField.vue'
import dayjs          from 'dayjs'

const toasts = useToastStore()

const step      = ref(0)
const archivo   = ref(null)
const fileInput = ref(null)
const dragOver  = ref(false)
const validating = ref(false)
const importing  = ref(false)
const validacion = ref(null)
const resultado  = ref(null)
const historial  = ref([])

const opciones = reactive({
  saltar_duplicados: true,
  nombre_lote: '',
})

const pasos = [
  { label: 'Cargar',    icon: '📂' },
  { label: 'Validar',   icon: '🔍' },
  { label: 'Confirmar', icon: '✅' },
  { label: 'Listo',     icon: '🎉' },
]

function formatDate(d) { return d ? dayjs(d).format('DD/MM/YYYY HH:mm') : '—' }

function onFileChange(e) { archivo.value = e.target.files?.[0] ?? null }
function onDrop(e)       { dragOver.value = false; archivo.value = e.dataTransfer.files?.[0] ?? null }

async function descargarPlantilla(formato = 'xlsx') {
  try {
    toasts.info(`Preparando descarga de plantilla ${formato.toUpperCase()}...`)
    const response = await api.get(`/importaciones/plantilla?formato=${formato}&download=1`, {
      responseType: 'blob'
    })
    const mime = formato === 'xlsx'
      ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      : 'text/csv;charset=utf-8;'
    const blob = new Blob([response.data], { type: mime })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `plantilla_simpatizantes.${formato}`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    toasts.success(`Plantilla ${formato.toUpperCase()} descargada exitosamente`)
  } catch (e) {
    toasts.error('Error al descargar la plantilla')
  }
}

async function validarArchivo() {
  if (!archivo.value || validating.value) return
  validating.value = true
  try {
    const fd = new FormData()
    fd.append('archivo', archivo.value)
    fd.append('campana_id', '1')
    fd.append('territorio_default_id', '1')
    const { data } = await api.post('/importaciones/validar', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    validacion.value = data.data ?? data
    step.value = 1
  } catch (e) {
    toasts.error(e?.response?.data?.message ?? 'Error al validar el archivo')
  } finally {
    validating.value = false
  }
}

async function procesarImportacion() {
  importing.value = true
  try {
    const fd = new FormData()
    fd.append('archivo', archivo.value)
    fd.append('campana_id', '1')
    fd.append('territorio_default_id', '1')
    fd.append('saltar_duplicados', opciones.saltar_duplicados ? '1' : '0')
    if (opciones.nombre_lote) fd.append('nombre_lote', opciones.nombre_lote)

    const { data } = await api.post('/importaciones', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    resultado.value = data.data ?? data
    step.value = 3
    cargarHistorial()
  } catch (e) {
    toasts.error(e?.response?.data?.message ?? 'Error al procesar la importación')
  } finally {
    importing.value = false
  }
}

function resetear() {
  step.value    = 0
  archivo.value = null
  validacion.value = null
  resultado.value  = null
  opciones.nombre_lote = ''
  if (fileInput.value) fileInput.value.value = ''
}

async function cargarHistorial() {
  try {
    const { data } = await api.get('/importaciones')
    historial.value = data.data ?? []
  } catch { historial.value = [] }
}

onMounted(cargarHistorial)
</script>
