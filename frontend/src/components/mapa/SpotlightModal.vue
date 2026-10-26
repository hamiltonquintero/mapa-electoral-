<template>
  <Teleport to="body">
    <Transition name="spotlight-fade">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-[10000] flex items-start justify-center pt-16 sm:pt-24 px-4 bg-gray-900/60 backdrop-blur-md"
        @click.self="cerrar"
        @keydown.esc="cerrar"
      >
        <div
          class="relative w-full max-w-2xl bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[80vh] transition-all"
        >
          <!-- ── Cabecera de Búsqueda ── -->
          <div class="p-4 border-b border-gray-100 flex items-center gap-3">
            <span class="text-gray-400 text-lg">🔍</span>
            <input
              ref="inputBusqueda"
              v-model="query"
              type="text"
              class="flex-1 bg-transparent border-none outline-none text-base sm:text-lg text-gray-900 placeholder-gray-400 font-medium"
              placeholder="Buscar municipio, puesto de votación, simpatizante o comuna… (Ctrl+K)"
              @keydown.down.prevent="navegarAbajo"
              @keydown.up.prevent="navegarArriba"
              @keydown.enter.prevent="seleccionarActual"
            />
            <span v-if="loading" class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin flex-shrink-0" />
            <button
              class="text-xs font-semibold px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-600 transition"
              @click="cerrar"
            >
              ESC
            </button>
          </div>

          <!-- ── Resultados de Búsqueda ── -->
          <div class="flex-1 overflow-y-auto p-3 space-y-4 max-h-[60vh] scrollbar-thin">
            <!-- Si no hay query -->
            <div v-if="!query.trim()" class="py-8 text-center text-gray-400 text-xs sm:text-sm">
              <p class="font-medium text-gray-500 mb-1">Búsqueda Rápida Universal</p>
              <p>Escribe el nombre de un municipio de Caldas, un puesto de votación o un simpatizante.</p>
              <div class="mt-4 flex flex-wrap justify-center gap-1.5">
                <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium cursor-pointer hover:bg-blue-100" @click="query = 'Manizales'">Manizales</span>
                <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium cursor-pointer hover:bg-blue-100" @click="query = 'Chinchiná'">Chinchiná</span>
                <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium cursor-pointer hover:bg-blue-100" @click="query = 'La Dorada'">La Dorada</span>
                <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium cursor-pointer hover:bg-blue-100" @click="query = 'Colegio'">Colegios</span>
              </div>
            </div>

            <!-- Sin resultados -->
            <div v-else-if="!loading && totalResultados === 0" class="py-10 text-center text-gray-400 text-sm">
              No se encontraron coincidencias para "{{ query }}"
            </div>

            <!-- Sección: Territorios y Municipios -->
            <div v-if="resultados.territorios?.length">
              <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider px-3 mb-1.5 flex items-center gap-1.5">
                <span>🏛️</span>
                <span>Territorios ({{ resultados.territorios.length }})</span>
              </p>
              <div class="space-y-1">
                <div
                  v-for="(item, idx) in resultados.territorios"
                  :key="'t-' + item.id"
                  class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition text-sm"
                  :class="indiceGlobal(idx, 'territorios') === indiceSeleccionado ? 'bg-blue-50 text-blue-900 font-bold' : 'hover:bg-gray-50 text-gray-700'"
                  @mouseenter="indiceSeleccionado = indiceGlobal(idx, 'territorios')"
                  @click="elegirItem(item, 'territorio')"
                >
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs flex-shrink-0">
                      {{ item.tipo === 'municipio' ? '🏛️' : '🏘️' }}
                    </span>
                    <div class="truncate">
                      <p class="font-bold text-gray-900 truncate">{{ item.nombre }}</p>
                      <p class="text-xs text-gray-500 capitalize">{{ item.tipo }} {{ item.padre ? '· ' + item.padre : '' }}</p>
                    </div>
                  </div>
                  <span class="text-xs text-blue-600 font-semibold flex items-center gap-1 flex-shrink-0">
                    Ir al mapa →
                  </span>
                </div>
              </div>
            </div>

            <!-- Sección: Puestos de Votación -->
            <div v-if="resultados.puestos?.length">
              <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider px-3 mb-1.5 flex items-center gap-1.5">
                <span>📍</span>
                <span>Puestos de Votación ({{ resultados.puestos.length }})</span>
              </p>
              <div class="space-y-1">
                <div
                  v-for="(item, idx) in resultados.puestos"
                  :key="'p-' + item.id"
                  class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition text-sm"
                  :class="indiceGlobal(idx, 'puestos') === indiceSeleccionado ? 'bg-blue-50 text-blue-900 font-bold' : 'hover:bg-gray-50 text-gray-700'"
                  @mouseenter="indiceSeleccionado = indiceGlobal(idx, 'puestos')"
                  @click="elegirItem(item, 'puesto')"
                >
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-red-100 text-red-600 flex items-center justify-center text-xs flex-shrink-0">
                      📍
                    </span>
                    <div class="truncate">
                      <p class="font-bold text-gray-900 truncate">{{ item.nombre }}</p>
                      <p class="text-xs text-gray-500 truncate">{{ item.municipio }} {{ item.direccion ? '· ' + item.direccion : '' }}</p>
                    </div>
                  </div>
                  <div class="text-right flex-shrink-0 pl-2">
                    <span class="text-xs font-bold text-gray-700">{{ item.total_mesas }} mesas</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sección: Simpatizantes -->
            <div v-if="resultados.simpatizantes?.length">
              <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider px-3 mb-1.5 flex items-center gap-1.5">
                <span>👥</span>
                <span>Simpatizantes ({{ resultados.simpatizantes.length }})</span>
              </p>
              <div class="space-y-1">
                <div
                  v-for="(item, idx) in resultados.simpatizantes"
                  :key="'s-' + item.id"
                  class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition text-sm"
                  :class="indiceGlobal(idx, 'simpatizantes') === indiceSeleccionado ? 'bg-blue-50 text-blue-900 font-bold' : 'hover:bg-gray-50 text-gray-700'"
                  @mouseenter="indiceSeleccionado = indiceGlobal(idx, 'simpatizantes')"
                  @click="elegirItem(item, 'simpatizante')"
                >
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                      {{ item.ha_votado ? '✓' : '👤' }}
                    </span>
                    <div class="truncate">
                      <p class="font-bold text-gray-900 truncate">{{ item.nombre_completo }}</p>
                      <p class="text-xs text-gray-500 truncate">{{ item.municipio }} · {{ item.puesto || 'Sin puesto' }} (Mesa {{ item.mesa || '—' }})</p>
                    </div>
                  </div>
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                    :class="item.ha_votado ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
                  >
                    {{ item.ha_votado ? 'Ya Votó' : 'Registrado' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- ── Footer Atajos ── -->
          <div class="px-4 py-2.5 bg-gray-50/90 border-t border-gray-100 text-[11px] text-gray-400 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span>↑↓ para navegar</span>
              <span>↵ para seleccionar</span>
              <span>ESC para salir</span>
            </div>
            <span class="font-semibold text-gray-500">Spotlight Electoral Caldas</span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import api from '@/services/api'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'select-territorio', 'select-puesto', 'select-simpatizante'])

const inputBusqueda = ref(null)
const query = ref('')
const loading = ref(false)
const resultados = ref({
  territorios: [],
  puestos: [],
  simpatizantes: [],
})
const indiceSeleccionado = ref(0)
let debounceTimer = null

const totalResultados = computed(() => {
  return (resultados.value.territorios?.length || 0) +
         (resultados.value.puestos?.length || 0) +
         (resultados.value.simpatizantes?.length || 0)
})

const listaPlana = computed(() => {
  const lista = []
  resultados.value.territorios?.forEach(t => lista.push({ item: t, type: 'territorio' }))
  resultados.value.puestos?.forEach(p => lista.push({ item: p, type: 'puesto' }))
  resultados.value.simpatizantes?.forEach(s => lista.push({ item: s, type: 'simpatizante' }))
  return lista
})

function indiceGlobal(idx, seccion) {
  let offset = 0
  if (seccion === 'puestos') {
    offset = resultados.value.territorios?.length || 0
  } else if (seccion === 'simpatizantes') {
    offset = (resultados.value.territorios?.length || 0) + (resultados.value.puestos?.length || 0)
  }
  return offset + idx
}

watch(() => props.modelValue, val => {
  if (val) {
    query.value = ''
    resultados.value = { territorios: [], puestos: [], simpatizantes: [] }
    indiceSeleccionado.value = 0
    nextTick(() => {
      inputBusqueda.value?.focus()
    })
  }
})

watch(query, (nuevaQuery) => {
  clearTimeout(debounceTimer)
  const q = nuevaQuery.trim()
  if (q.length < 2) {
    resultados.value = { territorios: [], puestos: [], simpatizantes: [] }
    loading.value = false
    return
  }

  loading.value = true
  debounceTimer = setTimeout(async () => {
    try {
      const resp = await api.get('/mapa/buscar', { params: { q } })
      resultados.value = resp.data?.data || { territorios: [], puestos: [], simpatizantes: [] }
      indiceSeleccionado.value = 0
    } catch (e) {
      console.error('Error en búsqueda Spotlight', e)
    } finally {
      loading.value = false
    }
  }, 220)
})

function navegarAbajo() {
  if (totalResultados.value === 0) return
  indiceSeleccionado.value = (indiceSeleccionado.value + 1) % totalResultados.value
}

function navegarArriba() {
  if (totalResultados.value === 0) return
  indiceSeleccionado.value = (indiceSeleccionado.value - 1 + totalResultados.value) % totalResultados.value
}

function seleccionarActual() {
  const elem = listaPlana.value[indiceSeleccionado.value]
  if (elem) {
    elegirItem(elem.item, elem.type)
  }
}

function elegirItem(item, tipo) {
  if (tipo === 'territorio') {
    emit('select-territorio', item)
  } else if (tipo === 'puesto') {
    emit('select-puesto', item)
  } else if (tipo === 'simpatizante') {
    emit('select-simpatizante', item)
  }
  cerrar()
}

function cerrar() {
  emit('update:modelValue', false)
}
</script>

<style scoped>
.spotlight-fade-enter-active,
.spotlight-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.spotlight-fade-enter-from,
.spotlight-fade-leave-to {
  opacity: 0;
  transform: scale(0.97);
}
</style>
