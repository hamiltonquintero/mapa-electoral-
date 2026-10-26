<template>
  <Teleport to="body">
    <div
      aria-live="assertive"
      class="pointer-events-none fixed inset-x-0 bottom-0 sm:bottom-4 sm:right-4 sm:left-auto z-50 flex flex-col gap-2 p-4 sm:p-0 sm:w-80"
    >
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex items-start gap-3 rounded-lg p-3.5 shadow-lg border text-sm"
          :class="typeClass(toast.type)"
        >
          <component :is="icon(toast.type)" class="w-5 h-5 flex-shrink-0 mt-0.5" />
          <p class="flex-1">{{ toast.message }}</p>
          <button class="flex-shrink-0 opacity-60 hover:opacity-100" @click="toastStore.remove(toast.id)">
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useToastStore } from '@/stores/toast'
import {
  CheckCircleIcon, ExclamationCircleIcon,
  InformationCircleIcon, ExclamationTriangleIcon, XMarkIcon,
} from '@heroicons/vue/20/solid'

const toastStore = useToastStore()
const toasts     = computed(() => toastStore.toasts)

const typeClass = (type) => ({
  success: 'bg-green-50  border-green-200 text-green-800',
  error:   'bg-red-50    border-red-200   text-red-800',
  warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
  info:    'bg-blue-50   border-blue-200  text-blue-800',
}[type] ?? 'bg-white border-gray-200 text-gray-800')

const icon = (type) => ({
  success: CheckCircleIcon,
  error:   ExclamationCircleIcon,
  warning: ExclamationTriangleIcon,
  info:    InformationCircleIcon,
}[type] ?? InformationCircleIcon)
</script>

<style scoped>
.toast-enter-active { transition: all 0.2s ease-out; }
.toast-leave-active { transition: all 0.15s ease-in; }
.toast-enter-from   { transform: translateX(24px); opacity: 0; }
.toast-leave-to     { transform: translateY(12px); opacity: 0; }
</style>
