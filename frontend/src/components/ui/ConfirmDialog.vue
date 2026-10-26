<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/40" @click="$emit('update:modelValue', false)" />

        <!-- Dialog -->
        <div class="relative bg-white rounded-xl shadow-2xl max-w-sm w-full p-6">
          <div class="flex items-start gap-4">
            <div :class="iconWrapClass">
              <component :is="iconComponent" class="w-6 h-6" :class="iconClass" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-gray-900 text-sm">{{ title }}</h3>
              <p class="mt-1 text-sm text-gray-500">{{ message }}</p>
            </div>
          </div>

          <div class="mt-5 flex justify-end gap-2">
            <button class="btn-secondary btn-sm" @click="$emit('update:modelValue', false)">
              {{ cancelLabel }}
            </button>
            <button :class="confirmBtnClass" @click="confirm">
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import {
  ExclamationTriangleIcon, TrashIcon, QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue:   { type: Boolean, default: false },
  title:        { type: String,  default: '¿Está seguro?' },
  message:      { type: String,  default: 'Esta acción no se puede deshacer.' },
  confirmLabel: { type: String,  default: 'Confirmar' },
  cancelLabel:  { type: String,  default: 'Cancelar' },
  variant:      { type: String,  default: 'warning' }, // warning | danger | info
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const iconComponent = computed(() => ({
  warning: ExclamationTriangleIcon,
  danger:  TrashIcon,
  info:    QuestionMarkCircleIcon,
}[props.variant]))

const iconWrapClass = computed(() => ({
  warning: 'w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0',
  danger:  'w-10 h-10 rounded-full bg-red-100   flex items-center justify-center flex-shrink-0',
  info:    'w-10 h-10 rounded-full bg-blue-100  flex items-center justify-center flex-shrink-0',
}[props.variant]))

const iconClass = computed(() => ({
  warning: 'text-yellow-600',
  danger:  'text-red-600',
  info:    'text-blue-600',
}[props.variant]))

const confirmBtnClass = computed(() =>
  props.variant === 'danger' ? 'btn-danger btn-sm' : 'btn-primary btn-sm',
)

function confirm() {
  emit('confirm')
  emit('update:modelValue', false)
}
</script>
