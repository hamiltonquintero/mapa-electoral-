<template>
  <aside
    class="fixed inset-y-0 left-0 z-30 flex flex-col bg-gray-900 text-white transition-all duration-200"
    :class="collapsed ? 'w-14' : 'w-[260px]'"
  >
    <!-- Logo -->
    <div class="flex h-14 items-center gap-3 px-3 border-b border-gray-700">
      <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center">
        <MapPinIcon class="w-5 h-5 text-white" />
      </div>
      <Transition name="fade">
        <span v-if="!collapsed" class="font-semibold text-sm leading-tight">
          Mapa Electoral<br>
          <span class="text-gray-400 font-normal text-xs">Caldas</span>
        </span>
      </Transition>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 overflow-y-auto py-3 space-y-0.5 px-2 scrollbar-thin">
      <NavItem
        v-for="item in navItems"
        :key="item.name"
        :item="item"
        :collapsed="collapsed"
      />
    </nav>

    <!-- Campaña activa -->
    <div v-if="!collapsed" class="px-3 py-2 border-t border-gray-700">
      <p class="text-xs text-gray-500 mb-1">Campaña activa</p>
      <p class="text-xs font-medium text-gray-200 truncate">
        {{ auth.user?.campana_activa?.nombre ?? 'Todas las campañas' }}
      </p>
    </div>

    <!-- Usuario -->
    <div class="border-t border-gray-700 p-2">
      <button
        class="flex items-center gap-2 w-full rounded-md p-2 hover:bg-gray-800 transition text-left"
        @click="$router.push('/perfil')"
      >
        <UserAvatar :name="auth.user?.name" size="sm" />
        <Transition name="fade">
          <div v-if="!collapsed" class="flex-1 min-w-0">
            <p class="text-xs font-medium text-gray-200 truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ auth.user?.roles?.[0]?.name }}</p>
          </div>
        </Transition>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { MapPinIcon } from '@heroicons/vue/24/solid'
import {
  MapIcon, UsersIcon, ArrowUpTrayIcon,
  ClipboardDocumentListIcon, ChartBarIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline'
import NavItem    from './NavItem.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'

defineProps({ collapsed: Boolean })

const auth = useAuthStore()

const navItems = computed(() => [
  {
    name: 'mapa',
    label: 'Mapa Electoral',
    icon: MapIcon,
    to: '/mapa',
  },
  {
    name: 'simpatizantes',
    label: 'Simpatizantes',
    icon: UsersIcon,
    to: '/simpatizantes',
    permission: 'simpatizante.ver',
  },
  {
    name: 'importacion',
    label: 'Importación',
    icon: ArrowUpTrayIcon,
    to: '/importacion',
    permission: 'importacion.realizar',
  },
  {
    name: 'auditoria',
    label: 'Auditoría',
    icon: ClipboardDocumentListIcon,
    to: '/auditoria',
    permission: 'auditoria.ver',
  },
  {
    name: 'reportes',
    label: 'Reportes',
    icon: ChartBarIcon,
    to: '/reportes',
    permission: 'reporte.ver',
  },
  {
    name: 'admin',
    label: 'Administración',
    icon: Cog6ToothIcon,
    to: '/admin',
    permission: 'usuario.ver',
  },
].filter((item) => !item.permission || auth.hasPermission(item.permission)))
</script>
