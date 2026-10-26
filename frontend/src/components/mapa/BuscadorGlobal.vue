<template>
  <button
    type="button"
    class="flex items-center gap-2 bg-gray-50 hover:bg-gray-100 text-gray-500 rounded-full px-3.5 py-1.5 text-xs transition border border-gray-200/80 shadow-xs group cursor-pointer"
    @click="abrirSpotlight"
    title="Buscar cualquier municipio, puesto o simpatizante (Ctrl + K)"
  >
    <MagnifyingGlassIcon class="w-3.5 h-3.5 text-gray-400 group-hover:text-blue-600 transition-colors" />
    <span class="hidden md:inline font-medium text-gray-600">Buscar municipio, puesto, persona…</span>
    <span class="md:hidden font-medium text-gray-600">Buscar…</span>
    <kbd class="hidden sm:inline-flex items-center gap-0.5 bg-white text-gray-400 group-hover:text-gray-600 rounded px-1.5 py-0.5 text-[10px] font-bold border border-gray-200 shadow-2xs">
      Ctrl K
    </kbd>
  </button>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid'

function abrirSpotlight() {
  window.dispatchEvent(new CustomEvent('open-spotlight'))
}

function handleKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    abrirSpotlight()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>
