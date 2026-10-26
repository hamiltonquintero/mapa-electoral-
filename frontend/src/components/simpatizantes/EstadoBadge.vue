<template>
  <span class="badge" :class="cfg.class">{{ cfg.label }}</span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  estado: { type: String, default: '' },
})

const cfg = computed(() => ({
  validado:            { class: 'badge-green',  label: 'Validado' },
  pendiente_evidencia: { class: 'badge-yellow', label: 'Pend. evidencia' },
  borrador:            { class: 'badge-gray',   label: 'Borrador' },
  rechazado:           { class: 'badge-red',    label: 'Rechazado' },
  retirado:            { class: 'badge-red',    label: 'Retirado' },
}[props.estado] ?? { class: 'badge-gray', label: props.estado ?? '—' }))
</script>
