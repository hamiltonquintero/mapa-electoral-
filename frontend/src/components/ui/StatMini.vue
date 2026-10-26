<template>
  <div class="rounded-md bg-white border border-gray-100 p-2 text-center shadow-sm">
    <p class="text-base font-bold" :class="valueClass">
      {{ typeof value === 'number' ? value.toLocaleString('es-CO') : value }}
    </p>
    <p class="text-xs text-gray-500 mt-0.5 leading-tight">{{ label }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  color: { type: String, default: 'blue' },
})

const valueClass = computed(() => ({
  blue:   'text-blue-700',
  green:  'text-green-700',
  yellow: 'text-yellow-700',
  red:    'text-red-700',
  gray:   'text-gray-700',
}[props.color] ?? 'text-gray-900'))
</script>
