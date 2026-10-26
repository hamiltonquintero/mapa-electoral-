<template>
  <button
    class="flex items-center justify-between w-full px-4 py-3 hover:bg-gray-50 transition text-left"
    @click="$emit('click')"
  >
    <div class="flex items-center gap-2 min-w-0">
      <!-- Indicador de color según volumen -->
      <span class="flex-shrink-0 w-2 h-2 rounded-full" :class="colorClass" />
      <div class="min-w-0">
        <p class="text-sm font-medium text-gray-900 truncate">{{ hijo.nombre }}</p>
        <p class="text-xs text-gray-500 capitalize">{{ hijo.tipo }}</p>
      </div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
      <span class="text-sm font-semibold text-gray-900">
        {{ (hijo.conteo ?? hijo.total_apoyos ?? 0).toLocaleString('es-CO') }}
      </span>
      <ChevronRightIcon class="w-4 h-4 text-gray-400" />
    </div>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronRightIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  hijo: { type: Object, required: true },
})
defineEmits(['click'])

const colorClass = computed(() => {
  const t = props.hijo.conteo ?? props.hijo.total_apoyos ?? 0
  if (t === 0)      return 'bg-gray-200'
  if (t < 50)       return 'bg-blue-200'
  if (t < 200)      return 'bg-blue-400'
  if (t < 500)      return 'bg-blue-600'
  return 'bg-blue-800'
})
</script>
