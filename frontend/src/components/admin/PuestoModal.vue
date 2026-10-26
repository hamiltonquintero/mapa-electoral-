<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('update:modelValue', false)" />
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">{{ puesto ? 'Editar puesto' : 'Nuevo puesto de votación' }}</h3>
            <button class="btn-ghost p-1" @click="$emit('update:modelValue', false)"><XMarkIcon class="w-4 h-4" /></button>
          </div>
          <form @submit.prevent="guardar" class="space-y-4">
            <FormField label="Nombre del puesto *" :error="errors.nombre">
              <input v-model="form.nombre" class="input" :class="{'input-error': errors.nombre}" placeholder="Ej: Colegio Nuestra Señora" />
            </FormField>
            <FormField label="ID de territorio *" :error="errors.territorio_id">
              <input v-model="form.territorio_id" class="input" :class="{'input-error': errors.territorio_id}" placeholder="ID del municipio o zona" />
            </FormField>
            <FormField label="Dirección">
              <input v-model="form.direccion" class="input" placeholder="Carrera 5 #10-20" />
            </FormField>
            <FormField label="Total de mesas *" :error="errors.total_mesas">
              <input v-model.number="form.total_mesas" type="number" min="1" class="input" :class="{'input-error': errors.total_mesas}" />
            </FormField>
            <div class="grid grid-cols-2 gap-3">
              <FormField label="Latitud">
                <input v-model.number="form.latitud" type="number" step="any" class="input" placeholder="5.0689" />
              </FormField>
              <FormField label="Longitud">
                <input v-model.number="form.longitud" type="number" step="any" class="input" placeholder="-75.5174" />
              </FormField>
            </div>
            <FormField label="Código Registraduría">
              <input v-model="form.codigo_registraduria" class="input font-mono" placeholder="Opcional" />
            </FormField>
            <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>
            <div class="flex justify-end gap-3">
              <button type="button" class="btn-secondary" @click="$emit('update:modelValue', false)">Cancelar</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                <LoadingSpinner v-if="saving" size="sm" />Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/20/solid'
import { puestoService } from '@/services/adminService'
import { useToastStore } from '@/stores/toast'
import FormField      from '@/components/ui/FormField.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

const props = defineProps({ modelValue: Boolean, puesto: { type: Object, default: null } })
const emit   = defineEmits(['update:modelValue', 'saved'])
const toasts = useToastStore()
const form   = reactive({ territorio_id: '', nombre: '', direccion: '', total_mesas: 1, latitud: null, longitud: null, codigo_registraduria: '' })
const errors = reactive({})
const saving = ref(false)

watch(() => props.modelValue, (v) => {
  Object.assign(errors, {})
  if (v && props.puesto) {
    Object.assign(form, {
      territorio_id: props.puesto.territorio_id, nombre: props.puesto.nombre,
      direccion: props.puesto.direccion ?? '', total_mesas: props.puesto.total_mesas,
      latitud: props.puesto.latitud, longitud: props.puesto.longitud,
      codigo_registraduria: props.puesto.codigo_registraduria ?? '',
    })
  } else if (v) {
    Object.assign(form, { territorio_id: '', nombre: '', direccion: '', total_mesas: 1, latitud: null, longitud: null, codigo_registraduria: '' })
  }
})

async function guardar() {
  saving.value = true
  try {
    if (props.puesto) await puestoService.update(props.puesto.id, form)
    else              await puestoService.create(form)
    toasts.success(props.puesto ? 'Puesto actualizado' : 'Puesto creado')
    emit('saved'); emit('update:modelValue', false)
  } catch (e) {
    const d = e?.response?.data
    if (d?.errors) Object.assign(errors, d.errors)
    else errors.general = d?.message ?? 'Error'
  } finally { saving.value = false }
}
</script>
