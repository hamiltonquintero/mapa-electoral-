<template>
  <RouterView v-slot="{ Component, route }">
    <Transition :name="route.meta.transition ?? 'fade'" mode="out-in">
      <component :is="Component" :key="route.path" />
    </Transition>
  </RouterView>

  <!-- Toast global -->
  <ToastContainer />
</template>

<script setup>
import { RouterView } from 'vue-router'
import ToastContainer from '@/components/ui/ToastContainer.vue'
</script>
