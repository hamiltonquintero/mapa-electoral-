<template>
  <div class="p-6 max-w-3xl mx-auto space-y-5">
    <!-- Cabecera -->
    <div class="flex items-center gap-3">
      <button class="btn-ghost btn-sm p-1" @click="$router.back()">
        <ArrowLeftIcon class="w-5 h-5" />
      </button>
      <h2 class="text-lg font-semibold text-gray-900">
        {{ isEdit ? 'Editar simpatizante' : 'Nuevo simpatizante' }}
      </h2>
    </div>

    <form @submit.prevent="guardar" novalidate>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Formulario principal -->
        <div class="lg:col-span-2 space-y-5">
          <!-- Datos personales -->
          <div class="card p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Datos personales</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <FormField label="Nombres *" :error="errors.nombres">
                <input v-model="form.nombres" class="input" :class="{ 'input-error': errors.nombres }"
                  placeholder="Ej: Juan Carlos" @input="limpiarError('nombres')" />
              </FormField>
              <FormField label="Apellidos *" :error="errors.apellidos">
                <input v-model="form.apellidos" class="input" :class="{ 'input-error': errors.apellidos }"
                  placeholder="Ej: Gómez Pérez" @input="limpiarError('apellidos')" />
              </FormField>
            </div>

            <FormField label="Número de cédula *" :error="errors.cedula">
              <div class="relative">
                <input
                  v-model="form.cedula"
                  type="text"
                  inputmode="numeric"
                  class="input pr-24"
                  :class="{ 'input-error': errors.cedula }"
                  placeholder="1234567890"
                  @blur="verificarDuplicado"
                  @input="limpiarError('cedula')"
                />
                <span
                  v-if="estadoCedula"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-xs"
                  :class="estadoCedula === 'duplicado' ? 'text-red-600' : 'text-green-600'"
                >
                  {{ estadoCedula === 'duplicado' ? '⚠ Duplicado' : '✓ Disponible' }}
                </span>
              </div>
            </FormField>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <FormField label="Teléfono" :error="errors.telefono">
                <input v-model="form.telefono" type="tel" class="input"
                  :class="{ 'input-error': errors.telefono }" placeholder="3001234567" />
              </FormField>
              <FormField label="Correo electrónico">
                <input v-model="form.correo" type="email" class="input" placeholder="usuario@correo.com" />
              </FormField>
            </div>

            <FormField label="Dirección o referencia">
              <input v-model="form.direccion" type="text" class="input" placeholder="Carrera 5 #10-20, Barrio X" />
            </FormField>
          </div>

          <!-- Ubicación -->
          <div class="card p-5 space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Ubicación territorial</h3>
            <FormField label="Municipio / Zona *" :error="errors.territorio_id">
              <input
                v-model="form.territorio_id"
                type="text"
                class="input"
                :class="{ 'input-error': errors.territorio_id }"
                placeholder="ID del territorio"
              />
            </FormField>
            <div class="grid grid-cols-2 gap-4">
              <FormField label="Puesto de votación">
                <input v-model="form.puesto_id" type="text" class="input" placeholder="ID del puesto" />
              </FormField>
              <FormField label="Mesa">
                <input v-model.number="form.mesa" type="number" min="1" class="input" placeholder="Ej: 3" />
              </FormField>
            </div>
          </div>

          <!-- Observaciones -->
          <div class="card p-5">
            <FormField label="Observaciones">
              <textarea v-model="form.observaciones" rows="3" class="input resize-none"
                placeholder="Notas adicionales…" />
            </FormField>
          </div>
        </div>

        <!-- Columna lateral: formulario físico + lote -->
        <div class="space-y-5">
          <!-- Carga de formulario físico -->
          <div class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Formulario físico *</h3>
            <div
              class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:border-primary-400 transition"
              :class="{ 'border-primary-500 bg-primary-50': dragOver }"
              @dragover.prevent="dragOver = true"
              @dragleave="dragOver = false"
              @drop.prevent="onDrop"
              @click="fileInput?.click()"
            >
              <input ref="fileInput" type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png"
                @change="onFileChange" />
              <template v-if="previewUrl">
                <img :src="previewUrl" class="max-h-48 mx-auto rounded object-contain mb-2" alt="Vista previa" />
                <p class="text-xs text-gray-500 truncate">{{ archivoSeleccionado?.name }}</p>
                <button type="button" class="text-xs text-red-600 mt-1 hover:underline"
                  @click.stop="limpiarArchivo">Quitar</button>
              </template>
              <template v-else>
                <ArrowUpTrayIcon class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                <p class="text-sm text-gray-600">Arrastra o haz clic para subir</p>
                <p class="text-xs text-gray-400 mt-1">JPG, PNG o PDF · Máx 5 MB</p>
              </template>
            </div>
            <p v-if="errors.formulario_id" class="mt-1 text-xs text-red-600">{{ errors.formulario_id }}</p>

            <FormField label="Fecha de firma *" :error="errors.fecha_firma_formulario" class="mt-3">
              <input v-model="form.fecha_firma_formulario" type="date" class="input"
                :class="{ 'input-error': errors.fecha_firma_formulario }" />
            </FormField>
          </div>

          <!-- Lote -->
          <div class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Lote de captura</h3>
            <FormField label="Lote">
              <input v-model="form.lote_id" type="text" class="input" placeholder="ID del lote (opcional)" />
            </FormField>
          </div>
        </div>
      </div>

      <!-- Error general -->
      <div v-if="errors.general" class="mt-4 rounded-md bg-red-50 border border-red-200 p-3">
        <p class="text-sm text-red-700">{{ errors.general }}</p>
      </div>

      <!-- Botones de acción -->
      <div class="flex justify-end gap-3 mt-6">
        <button type="button" class="btn-secondary" @click="$router.back()">Cancelar</button>
        <button type="submit" class="btn-primary" :disabled="saving">
          <LoadingSpinner v-if="saving" size="sm" />
          {{ saving ? 'Guardando…' : isEdit ? 'Guardar cambios' : 'Crear simpatizante' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline'
import { simpatizanteService } from '@/services/simpatizanteService'
import { useToastStore }       from '@/stores/toast'
import FormField               from '@/components/ui/FormField.vue'
import LoadingSpinner          from '@/components/ui/LoadingSpinner.vue'
import api                     from '@/services/api'

const route  = useRoute()
const router = useRouter()
const toasts = useToastStore()

const id     = route.params.id
const isEdit = computed(() => !!id)

const form = reactive({
  nombres: '', apellidos: '', cedula: '', telefono: '', correo: '',
  direccion: '', territorio_id: '', puesto_id: '', mesa: null,
  observaciones: '', formulario_id: null, fecha_firma_formulario: '',
  lote_id: '', campana_id: null,
})
const errors = reactive({})
const saving  = ref(false)
const estadoCedula = ref(null) // null | 'verificando' | 'disponible' | 'duplicado'

/* Archivo adjunto */
const fileInput          = ref(null)
const archivoSeleccionado = ref(null)
const previewUrl          = ref(null)
const dragOver            = ref(false)

function limpiarError(field) { errors[field] = undefined }

function onFileChange(e) {
  const file = e.target.files?.[0]
  if (file) procesarArchivo(file)
}

function onDrop(e) {
  dragOver.value = false
  const file = e.dataTransfer.files?.[0]
  if (file) procesarArchivo(file)
}

function procesarArchivo(file) {
  if (file.size > 5 * 1024 * 1024) { toasts.error('El archivo supera los 5 MB'); return }
  archivoSeleccionado.value = file
  if (file.type.startsWith('image/')) {
    previewUrl.value = URL.createObjectURL(file)
  } else {
    previewUrl.value = null
  }
}

function limpiarArchivo() {
  archivoSeleccionado.value = null
  previewUrl.value = null
  form.formulario_id = null
  if (fileInput.value) fileInput.value.value = ''
}

async function subirFormulario() {
  if (!archivoSeleccionado.value) return null
  const fd = new FormData()
  fd.append('archivo', archivoSeleccionado.value)
  fd.append('fecha_firma', form.fecha_firma_formulario)
  const { data } = await api.post('/formularios', fd, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data.data?.id ?? null
}

async function verificarDuplicado() {
  if (!form.cedula || form.cedula.length < 5) return
  estadoCedula.value = 'verificando'
  try {
    const { data } = await simpatizanteService.buscarDuplicados(form.cedula, form.campana_id)
    estadoCedula.value = data.data?.length ? 'duplicado' : 'disponible'
  } catch {
    estadoCedula.value = null
  }
}

function validar() {
  Object.keys(errors).forEach((k) => delete errors[k])
  let ok = true

  if (!form.nombres.trim())    { errors.nombres    = 'Requerido'; ok = false }
  if (!form.apellidos.trim())  { errors.apellidos  = 'Requerido'; ok = false }
  if (!form.cedula.trim())     { errors.cedula     = 'Requerido'; ok = false }
  if (!form.territorio_id)     { errors.territorio_id = 'Selecciona el municipio/zona'; ok = false }
  if (!isEdit.value) {
    if (!archivoSeleccionado.value && !form.formulario_id) {
      errors.formulario_id = 'El formulario físico es obligatorio'
      ok = false
    }
    if (!form.fecha_firma_formulario) {
      errors.fecha_firma_formulario = 'Requerida'
      ok = false
    }
  }

  return ok
}

async function guardar() {
  if (!validar() || saving.value) return
  saving.value = true

  try {
    // 1. Subir formulario físico si hay archivo nuevo
    if (archivoSeleccionado.value) {
      form.formulario_id = await subirFormulario()
    }

    const payload = { ...form }

    if (isEdit.value) {
      await simpatizanteService.update(id, payload)
      toasts.success('Simpatizante actualizado correctamente')
    } else {
      await simpatizanteService.create(payload)
      toasts.success('Simpatizante creado correctamente')
    }

    router.push('/simpatizantes')
  } catch (e) {
    const data = e?.response?.data
    if (data?.errors) {
      Object.assign(errors, data.errors)
    } else {
      errors.general = data?.message ?? 'Error al guardar'
    }
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  if (isEdit.value) {
    try {
      const { data } = await simpatizanteService.get(id)
      const s = data.data ?? data
      Object.assign(form, {
        nombres:    s.nombres,
        apellidos:  s.apellidos,
        telefono:   s.telefono,
        correo:     s.correo,
        direccion:  s.direccion,
        territorio_id: s.territorio_id,
        puesto_id:  s.puesto_id,
        mesa:       s.mesa,
        observaciones: s.observaciones,
        lote_id:    s.lote_id,
        campana_id: s.campana_id,
      })
    } catch {
      toasts.error('Error al cargar el registro')
    }
  }
})
</script>
