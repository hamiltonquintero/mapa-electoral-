<template>
  <nav aria-label="Jerarquía territorial" class="flex flex-wrap items-center gap-1">
    <template v-for="(item, i) in items" :key="item.id ?? 'root'">
      <span v-if="i > 0" class="text-gray-400 text-xs">/</span>

      <!-- Último ítem (activo, no clickeable) -->
      <span
        v-if="i === items.length - 1"
        class="text-xs font-semibold text-gray-900 truncate max-w-[140px]"
      >
        {{ item.nombre }}
      </span>

      <!-- Ítems anteriores (clickeables) -->
      <button
        v-else
        class="text-xs text-primary-600 hover:underline truncate max-w-[110px]"
        @click="$emit('navegar', item.id)"
      >
        {{ item.nombre }}
      </button>
    </template>
  </nav>
</template>

<script setup>
defineProps({ items: { type: Array, default: () => [] } })
defineEmits(['navegar'])
</script>
