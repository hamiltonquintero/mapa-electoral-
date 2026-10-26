<template>
  <div class="px-4 py-3">
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0">
        <p class="text-sm font-medium text-gray-900 truncate">{{ puesto.nombre }}</p>
        <p class="text-xs text-gray-500 truncate">{{ puesto.direccion }}</p>
      </div>
      <div class="flex-shrink-0 text-right">
        <p class="text-sm font-semibold text-gray-900">{{ (puesto.total_apoyos ?? 0).toLocaleString('es-CO') }}</p>
        <p class="text-xs text-gray-400">apoyos</p>
      </div>
    </div>

    <!-- Desglose por mesa si está disponible -->
    <div v-if="puesto.mesas?.length" class="mt-2 flex flex-wrap gap-1">
      <span
        v-for="mesa in puesto.mesas"
        :key="mesa.mesa"
        class="inline-flex items-center gap-1 text-xs bg-gray-100 rounded px-1.5 py-0.5"
      >
        Mesa {{ mesa.mesa }}: <strong>{{ mesa.total }}</strong>
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({ puesto: { type: Object, required: true } })
</script>
