<template>
  <div
    class="flex-shrink-0 rounded-full bg-primary-700 flex items-center justify-center font-semibold text-white select-none"
    :class="sizeClass"
  >
    {{ initials }}
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: { type: String, default: '' },
  size: { type: String, default: 'md' }, // sm | md | lg
})

const initials = computed(() => {
  if (!props.name) return '?'
  return props.name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? '')
    .join('')
})

const sizeClass = computed(() => ({
  sm: 'w-7 h-7 text-xs',
  md: 'w-8 h-8 text-sm',
  lg: 'w-10 h-10 text-base',
}[props.size]))
</script>
