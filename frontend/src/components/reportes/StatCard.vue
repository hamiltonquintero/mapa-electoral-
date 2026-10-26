<template>
  <div class="card p-4 flex items-center gap-3">
    <span class="text-2xl flex-shrink-0">{{ icon }}</span>
    <div>
      <p class="text-xl font-bold" :class="colorClass">
        {{ typeof value === 'number' ? value.toLocaleString('es-CO') : value }}
      </p>
      <p class="text-xs text-gray-500">{{ label }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: String, value: [Number, String],
  color: { type: String, default: 'blue' },
  icon:  { type: String, default: '📊' },
})

const colorClass = computed(() => ({
  blue:   'text-blue-700',
  green:  'text-green-700',
  yellow: 'text-yellow-700',
  red:    'text-red-700',
}[props.color] ?? 'text-gray-900'))
</script>
