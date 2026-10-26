<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('update:modelValue', false)" />
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Exportar logs de auditoría</h3>
            <button class="btn-ghost p-1" @click="$emit('update:modelValue', false)">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>

          <p class="text-sm text-gray-500">
            Se exportarán hasta 50,000 eventos según los filtros activos. La exportación queda registrada en auditoría.
          </p>

          <FormField label="Motivo de la exportación *" :error="errors.motivo">
            <textarea
              v-model="form.motivo"
              rows="3"
              class="input resize-none"
              :class="{ 'input-error': errors.motivo }"
              placeholder="Ej: Revisión trimestral de seguridad, auditoría legal…"
            />
          </FormField>

          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.incluir_detalle" type="checkbox" class="rounded border-gray-300 text-primary-600" />
            <span class="text-sm text-gray-700">Incluir columna de detalle JSON</span>
          </label>

          <div class="flex justify-end gap-3 pt-2">
            <button class="btn-secondary" @click="$emit('update:modelValue', false)">Cancelar</button>
            <button class="btn-primary" :disabled="loading" @click="exportar">
              <LoadingSpinner v-if="loading" size="sm" />
              {{ loading ? 'Exportando…' : 'Descargar CSV' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/20/solid'
import api            from '@/services/api'
import { useToastStore } from '@/stores/toast'
import FormField      from '@/components/ui/FormField.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  filtros:    { type: Object,  default: () => ({}) },
})
const emit   = defineEmits(['update:modelValue'])
const toasts = useToastStore()

const form   = reactive({ motivo: '', incluir_detalle: false })
const errors = reactive({ motivo: '' })
const loading = ref(false)

async function exportar() {
  errors.motivo = ''
  if (!form.motivo.trim()) { errors.motivo = 'El motivo es obligatorio'; return }
  if (form.motivo.length < 10) { errors.motivo = 'Mínimo 10 caracteres'; return }

  loading.value = true
  try {
    const payload = {
      motivo: form.motivo,
      incluir_detalle: form.incluir_detalle,
      ...props.filtros,
    }

    const response = await api.post('/auditoria/exportar', payload, { responseType: 'blob' })

    // Disparar descarga en el navegador
    const url  = URL.createObjectURL(new Blob([response.data], { type: 'text/csv' }))
    const link = document.createElement('a')
    const date = new Date().toISOString().slice(0, 10)
    link.href  = url
    link.download = `auditoria_${date}.csv`
    link.click()
    URL.revokeObjectURL(url)

    emit('update:modelValue', false)
    toasts.success('Exportación descargada correctamente')
    form.motivo = ''
  } catch {
    toasts.error('Error al exportar la auditoría')
  } finally {
    loading.value = false
  }
}
</script>
