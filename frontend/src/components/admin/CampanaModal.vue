<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('update:modelValue', false)" />
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">{{ campana ? 'Editar campaña' : 'Nueva campaña' }}</h3>
            <button class="btn-ghost p-1" @click="$emit('update:modelValue', false)">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
          <form @submit.prevent="guardar" class="space-y-4">
            <FormField label="Nombre *" :error="errors.nombre">
              <input v-model="form.nombre" class="input" :class="{'input-error': errors.nombre}" />
            </FormField>
            <FormField label="Descripción">
              <textarea v-model="form.descripcion" rows="2" class="input resize-none" />
            </FormField>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.activa" type="checkbox" class="rounded border-gray-300 text-primary-600" />
              <span class="text-sm text-gray-700">Campaña activa</span>
            </label>
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
import { campanaService } from '@/services/adminService'
import { useToastStore }  from '@/stores/toast'
import FormField      from '@/components/ui/FormField.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

const props = defineProps({ modelValue: Boolean, campana: { type: Object, default: null } })
const emit   = defineEmits(['update:modelValue', 'saved'])
const toasts = useToastStore()
const form   = reactive({ nombre: '', descripcion: '', activa: true })
const errors = reactive({})
const saving = ref(false)

watch(() => props.modelValue, (v) => {
  if (v) {
    Object.assign(errors, {})
    Object.assign(form, props.campana
      ? { nombre: props.campana.nombre, descripcion: props.campana.descripcion ?? '', activa: props.campana.activa }
      : { nombre: '', descripcion: '', activa: true })
  }
})

async function guardar() {
  saving.value = true
  try {
    if (props.campana) await campanaService.update(props.campana.id, form)
    else               await campanaService.create(form)
    toasts.success(props.campana ? 'Campaña actualizada' : 'Campaña creada')
    emit('saved'); emit('update:modelValue', false)
  } catch (e) {
    errors.general = e?.response?.data?.message ?? 'Error'
  } finally { saving.value = false }
}
</script>
