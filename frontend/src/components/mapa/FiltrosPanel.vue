<template>
  <div>
    <button
      class="flex items-center justify-between w-full px-4 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 transition"
      @click="open = !open"
    >
      <span class="flex items-center gap-1.5">
        <FunnelIcon class="w-3.5 h-3.5" />
        Filtros
        <span v-if="activeCount" class="badge-blue text-xs px-1.5 py-0">{{ activeCount }}</span>
      </span>
      <ChevronDownIcon class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" />
    </button>

    <Transition name="slide">
      <div v-if="open" class="px-4 pb-3 space-y-2 bg-white">
        <!-- Campaña -->
        <div>
          <label class="label text-xs">Campaña</label>
          <select v-model="local.campana_id" class="input text-xs" @change="emit">
            <option :value="null">Todas las campañas</option>
            <option v-for="c in campanas" :key="c.id" :value="c.id">{{ c.nombre }}</option>
          </select>
        </div>

        <!-- Estado -->
        <div>
          <label class="label text-xs">Estado</label>
          <select v-model="local.estado" class="input text-xs" @change="emit">
            <option :value="null">Todos los estados</option>
            <option value="validado">Validado</option>
            <option value="pendiente_evidencia">Pendiente evidencia</option>
            <option value="borrador">Borrador</option>
          </select>
        </div>

        <button v-if="activeCount" class="text-xs text-red-600 hover:underline" @click="reset">
          Limpiar filtros
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { FunnelIcon, ChevronDownIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  filtros: { type: Object, default: () => ({}) },
})
const emits = defineEmits(['update'])

const open  = ref(false)
const local = ref({ campana_id: null, estado: null, ...props.filtros })

// Placeholder — en producción vendrían de un store/API
const campanas = ref([])

const activeCount = computed(() =>
  Object.values(local.value).filter((v) => v !== null && v !== undefined && v !== '').length,
)

watch(() => props.filtros, (v) => { local.value = { ...local.value, ...v } }, { deep: true })

function emit()  { emits('update', { ...local.value }) }
function reset() { local.value = { campana_id: null, estado: null }; emit() }
</script>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: max-height 0.2s ease, opacity 0.2s ease; overflow: hidden; }
.slide-enter-from, .slide-leave-to       { max-height: 0; opacity: 0; }
.slide-enter-to, .slide-leave-from       { max-height: 200px; opacity: 1; }
</style>
