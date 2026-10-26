<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('update:modelValue', false)" />
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">
              {{ usuario ? 'Editar usuario' : 'Nuevo usuario' }}
            </h3>
            <button class="btn-ghost p-1" @click="$emit('update:modelValue', false)">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>

          <form class="p-5 space-y-4" @submit.prevent="guardar">
            <FormField label="Nombre completo *" :error="errors.name">
              <input v-model="form.name" class="input" :class="{'input-error': errors.name}" />
            </FormField>
            <FormField label="Correo electrónico *" :error="errors.email">
              <input v-model="form.email" type="email" class="input" :class="{'input-error': errors.email}" />
            </FormField>
            <template v-if="!usuario">
              <FormField label="Contraseña *" :error="errors.password">
                <input v-model="form.password" type="password" class="input" :class="{'input-error': errors.password}" />
              </FormField>
              <FormField label="Confirmar contraseña">
                <input v-model="form.password_confirmation" type="password" class="input" />
              </FormField>
            </template>
            <FormField label="Rol *" :error="errors.rol">
              <select v-model="form.rol" class="input" :class="{'input-error': errors.rol}">
                <option value="">Selecciona un rol</option>
                <option v-for="r in roles" :key="r.name" :value="r.name">{{ r.name }}</option>
              </select>
            </FormField>
            <FormField label="Estado" v-if="usuario">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.activo" type="checkbox" class="rounded border-gray-300 text-primary-600" />
                <span class="text-sm text-gray-700">Usuario activo</span>
              </label>
            </FormField>

            <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

            <div class="flex justify-end gap-3 pt-2">
              <button type="button" class="btn-secondary" @click="$emit('update:modelValue', false)">Cancelar</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                <LoadingSpinner v-if="saving" size="sm" />
                {{ saving ? 'Guardando…' : 'Guardar' }}
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
import { usuarioService } from '@/services/adminService'
import { useToastStore }  from '@/stores/toast'
import FormField      from '@/components/ui/FormField.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  usuario:    { type: Object,  default: null },
  roles:      { type: Array,   default: () => [] },
})
const emit   = defineEmits(['update:modelValue', 'saved'])
const toasts = useToastStore()

const form   = reactive({ name: '', email: '', password: '', password_confirmation: '', rol: '', activo: true })
const errors = reactive({})
const saving = ref(false)

watch(() => props.modelValue, (v) => {
  if (v) {
    Object.assign(errors, { name: '', email: '', password: '', rol: '', general: '' })
    if (props.usuario) {
      Object.assign(form, {
        name: props.usuario.name, email: props.usuario.email,
        rol: props.usuario.roles?.[0]?.name ?? '', activo: props.usuario.activo,
        password: '', password_confirmation: '',
      })
    } else {
      Object.assign(form, { name: '', email: '', password: '', password_confirmation: '', rol: '', activo: true })
    }
  }
})

async function guardar() {
  Object.keys(errors).forEach(k => delete errors[k])
  saving.value = true
  try {
    if (props.usuario) {
      await usuarioService.update(props.usuario.id, form)
      if (form.rol && form.rol !== props.usuario.roles?.[0]?.name) {
        await usuarioService.asignarRol(props.usuario.id, form.rol)
      }
    } else {
      await usuarioService.create(form)
    }
    toasts.success(props.usuario ? 'Usuario actualizado' : 'Usuario creado')
    emit('saved')
    emit('update:modelValue', false)
  } catch (e) {
    const data = e?.response?.data
    if (data?.errors) Object.assign(errors, data.errors)
    else errors.general = data?.message ?? 'Error al guardar'
  } finally {
    saving.value = false
  }
}
</script>
