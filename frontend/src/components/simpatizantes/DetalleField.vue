<template>
  <div>
    <dt class="text-xs text-gray-500 mb-0.5" :class="{ 'text-xs': small }">{{ label }}</dt>
    <dd class="font-medium text-gray-900" :class="small ? 'text-xs' : 'text-sm'">{{ value }}</dd>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: '—' },
  small: { type: Boolean, default: false },
})
</script>
