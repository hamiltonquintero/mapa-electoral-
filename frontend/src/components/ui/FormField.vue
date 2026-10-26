<template>
  <div :class="containerClass">
    <label v-if="label" class="label">{{ label }}</label>
    <slot />
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    <p v-if="hint && !error" class="mt-1 text-xs text-gray-400">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label:  { type: String,  default: '' },
  error:  { type: String,  default: '' },
  hint:   { type: String,  default: '' },
  class:  { type: String,  default: '' },
})

const containerClass = computed(() => props.class)
</script>
