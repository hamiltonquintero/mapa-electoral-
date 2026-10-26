<template>
  <div class="min-h-full flex flex-col items-center justify-center text-center p-8">
    <p class="text-6xl font-bold text-gray-200 mb-4">404</p>
    <h1 class="text-xl font-semibold text-gray-900 mb-2">Página no encontrada</h1>
    <p class="text-gray-500 mb-6">La ruta que buscas no existe o no tienes acceso.</p>
    <RouterLink to="/mapa" class="btn-primary">Volver al mapa</RouterLink>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>
