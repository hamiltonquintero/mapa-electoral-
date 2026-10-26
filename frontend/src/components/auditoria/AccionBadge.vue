<template>
  <span class="badge" :class="cfg.class">{{ cfg.label }}</span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ accion: { type: String, default: '' } })

const cfg = computed(() => {
  const a = props.accion ?? ''
  if (a.includes('eliminado') || a.includes('borrado') || a.includes('supresion'))
    return { class: 'badge-red',    label: props.accion }
  if (a.includes('creado') || a.includes('subido'))
    return { class: 'badge-green',  label: props.accion }
  if (a.includes('actualizado') || a.includes('cambiado') || a.includes('asignado'))
    return { class: 'badge-blue',   label: props.accion }
  if (a.includes('exportacion') || a.includes('importacion'))
    return { class: 'badge-yellow', label: props.accion }
  if (a.includes('login') || a.includes('logout') || a.includes('2fa'))
    return { class: 'badge-gray',   label: props.accion }
  return { class: 'badge-gray', label: props.accion }
})
</script>
