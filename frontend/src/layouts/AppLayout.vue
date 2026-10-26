<template>
  <div class="flex h-full">
    <!-- Sidebar de navegación -->
    <AppSidebar :collapsed="sidebarCollapsed" @toggle="sidebarCollapsed = !sidebarCollapsed" />

    <!-- Contenido principal -->
    <div
      class="flex flex-col flex-1 overflow-hidden transition-all duration-200"
      :class="sidebarCollapsed ? 'ml-14' : 'ml-[260px]'"
    >
      <!-- Header -->
      <AppHeader @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed" />

      <!-- Vista activa -->
      <main class="flex-1 overflow-auto">
        <RouterView v-slot="{ Component, route }">
          <Transition :name="route.meta.transition ?? 'fade'" mode="out-in">
            <component :is="Component" :key="route.path" />
          </Transition>
        </RouterView>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppHeader  from '@/components/layout/AppHeader.vue'

const route = useRoute()
const sidebarCollapsed = ref(route.path === '/mapa')

watch(() => route.path, (p) => {
  if (p === '/mapa') {
    sidebarCollapsed.value = true
  }
})
</script>
