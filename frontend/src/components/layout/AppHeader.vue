<template>
  <header class="h-14 bg-white border-b border-gray-200 flex items-center gap-3 px-4 flex-shrink-0 z-20">
    <!-- Toggle sidebar (móvil) -->
    <button class="btn-ghost p-1.5 -ml-1" @click="$emit('toggle-sidebar')">
      <Bars3Icon class="w-5 h-5" />
    </button>

    <!-- Título de la ruta actual -->
    <h1 class="text-sm font-semibold text-gray-900 hidden sm:block">
      {{ route.meta.title ?? 'Mapa Electoral Caldas' }}
    </h1>

    <div class="flex-1" />

    <!-- Buscador global rápido -->
    <BuscadorGlobal />

    <!-- Notificaciones / acciones rápidas -->
    <button class="btn-ghost p-1.5" @click="auth.logout(); $router.push('/login')">
      <ArrowRightOnRectangleIcon class="w-5 h-5" title="Cerrar sesión" />
    </button>
  </header>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Bars3Icon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline'
import BuscadorGlobal from '@/components/mapa/BuscadorGlobal.vue'

defineEmits(['toggle-sidebar'])

const route = useRoute()
const auth  = useAuthStore()
</script>
