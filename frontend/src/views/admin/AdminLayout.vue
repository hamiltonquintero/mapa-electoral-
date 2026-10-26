<template>
  <div class="p-6 max-w-7xl mx-auto space-y-4">
    <!-- Cabecera con tabs de sección -->
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Administración</h2>
    </div>

    <div class="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit flex-wrap">
      <RouterLink
        v-for="tab in tabs"
        :key="tab.to"
        :to="tab.to"
        class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
        :class="$route.path.startsWith(tab.to)
          ? 'bg-white text-gray-900 shadow-sm'
          : 'text-gray-500 hover:text-gray-900'"
      >
        {{ tab.label }}
      </RouterLink>
    </div>

    <RouterView />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const tabs = computed(() => [
  { to: '/admin/usuarios',  label: 'Usuarios',         show: auth.hasPermission('usuario.ver') },
  { to: '/admin/campanas',  label: 'Campañas',          show: auth.hasPermission('campana.ver') },
  { to: '/admin/candidatos',label: 'Candidatos',        show: auth.hasPermission('campana.ver') },
  { to: '/admin/puestos',              label: 'Puestos votación',    show: auth.hasPermission('catalogo.ver') },
  { to: '/admin/solicitudes-titular',  label: 'Solicitudes ARCO',    show: auth.hasPermission('sistema.administrar') },
].filter(t => t.show))
</script>
