<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('update:modelValue', false)" />
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">{{ candidato ? 'Editar candidato' : 'Nuevo candidato' }}</h3>
            <button class="btn-ghost p-1" @click="$emit('update:modelValue', false)"><XMarkIcon class="w-4 h-4" /></button>
          </div>
          <form @submit.prevent="guardar" class="space-y-4">
            <FormField label="Campaña *" :error="errors.campana_id">
              <select v-model="form.campana_id" class="input" :class="{'input-error': errors.campana_id}">
                <option value="">Selecciona campaña</option>
                <option v-for="c in campanas" :key="c.id" :value="c.id">{{ c.nombre }}</option>
              </select>
            </FormField>
            <FormField label="Nombre *" :error="errors.nombre">
              <input v-model="form.nombre" class="input" :class="{'input-error': errors.nombre}" />
            </FormField>
            <FormField label="Cargo *" :error="errors.cargo">
              <select v-model="form.cargo" class="input" :class="{'input-error': errors.cargo}">
                <option value="">Selecciona cargo</option>
                <option value="gobernacion">Gobernación</option>
                <option value="alcaldia">Alcaldía</option>
                <option value="asamblea">Asamblea</option>
              </select>
            </FormField>
            <FormField label="Color corporativo">
              <div class="flex items-center gap-2">
                <input v-model="form.color" type="color" class="w-10 h-10 rounded border border-gray-300 cursor-pointer" />
                <input v-model="form.color" type="text" class="input font-mono" placeholder="#2563eb" />
              </div>
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
import { candidatoService } from '@/services/adminService'
import { useToastStore }    from '@/stores/toast'
import FormField      from '@/components/ui/FormField.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

const props = defineProps({
  modelValue: Boolean,
  candidato: { type: Object, default: null },
  campanas:  { type: Array,  default: () => [] },
})
const emit   = defineEmits(['update:modelValue', 'saved'])
const toasts = useToastStore()
const form   = reactive({ campana_id: '', nombre: '', cargo: '', color: '#2563eb' })
const errors = reactive({})
const saving = ref(false)

watch(() => props.modelValue, (v) => {
  if (v) {
    Object.assign(errors, {})
    Object.assign(form, props.candidato
      ? { campana_id: props.candidato.campana_id, nombre: props.candidato.nombre,
          cargo: props.candidato.cargo, color: props.candidato.color ?? '#2563eb' }
      : { campana_id: '', nombre: '', cargo: '', color: '#2563eb' })
  }
})

async function guardar() {
  saving.value = true
  try {
    if (props.candidato) await candidatoService.update(props.candidato.id, form)
    else                  await candidatoService.create(form)
    toasts.success(props.candidato ? 'Candidato actualizado' : 'Candidato creado')
    emit('saved'); emit('update:modelValue', false)
  } catch (e) {
    const d = e?.response?.data
    if (d?.errors) Object.assign(errors, d.errors)
    else errors.general = d?.message ?? 'Error'
  } finally { saving.value = false }
}
</script>
