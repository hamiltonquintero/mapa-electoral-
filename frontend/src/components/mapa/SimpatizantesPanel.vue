<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[1100] flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="$emit('close')">
      <!-- Panel -->
      <div class="w-full sm:w-[480px] max-h-[85vh] bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-slide-up">

        <!-- ── Header ────────────────────────────────────────────── -->
        <div class="flex-shrink-0 bg-gradient-to-r from-blue-700 to-blue-900 text-white px-5 py-4">
          <div class="flex items-start justify-between mb-3">
            <div class="min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <UsersIcon class="w-4 h-4 opacity-80" />
                <span class="text-xs font-medium uppercase tracking-wider opacity-80">Simpatizantes</span>
              </div>
              <h3 class="text-base font-bold truncate">{{ titulo }}</h3>
              <p class="text-xs opacity-70 mt-0.5">{{ subtitulo }}</p>
            </div>
            <button
              class="ml-3 w-7 h-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center flex-shrink-0 transition"
              @click="$emit('close')"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>

          <!-- Stats rápidas -->
          <div class="grid grid-cols-3 gap-2">
            <div class="bg-white/15 rounded-lg p-2 text-center">
              <p class="text-xl font-bold">{{ total.toLocaleString('es-CO') }}</p>
              <p class="text-xs opacity-70">Total</p>
            </div>
            <div class="bg-white/15 rounded-lg p-2 text-center">
              <p class="text-xl font-bold text-emerald-300">{{ totalValidados.toLocaleString('es-CO') }}</p>
              <p class="text-xs opacity-70">Validados</p>
            </div>
            <div class="bg-white/15 rounded-lg p-2 text-center">
              <p class="text-xl font-bold text-amber-300">{{ totalPendientes.toLocaleString('es-CO') }}</p>
              <p class="text-xs opacity-70">Pendientes</p>
            </div>
          </div>
        </div>

        <!-- ── Filtros ────────────────────────────────────────────── -->
        <div class="flex-shrink-0 px-4 py-2 border-b border-gray-100 flex items-center gap-2">
          <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 flex-shrink-0" />
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre..."
            class="flex-1 text-sm outline-none text-gray-700 placeholder-gray-400"
            @input="onBuscar"
          />
          <select
            v-model="filtroEstado"
            class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 text-gray-600 outline-none"
            @change="cargar(1)"
          >
            <option value="">Todos</option>
            <option value="validado">Validados</option>
            <option value="borrador">Pendientes</option>
          </select>
        </div>

        <!-- ── Lista ──────────────────────────────────────────────── -->
        <div class="flex-1 overflow-y-auto">

          <!-- Loading -->
          <div v-if="loading" class="flex flex-col items-center justify-center gap-3 py-16">
            <div class="w-8 h-8 border-3 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
            <p class="text-sm text-gray-400">Cargando simpatizantes…</p>
          </div>

          <!-- Lista de simpatizantes -->
          <div v-else-if="simpatizantesFiltrados.length">
            <div
              v-for="s in simpatizantesFiltrados"
              :key="s.id"
              class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors"
            >
              <!-- Avatar inicial -->
              <div
                class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold"
                :class="s.estado === 'validado' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
              >
                {{ s.nombres?.charAt(0) ?? '?' }}{{ s.apellidos?.charAt(0) ?? '' }}
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ s.nombre_completo }}</p>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                  <span
                    class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                    :class="s.estado === 'validado'
                      ? 'bg-emerald-100 text-emerald-700'
                      : 'bg-amber-100 text-amber-700'"
                  >
                    {{ s.estado === 'validado' ? 'Validado' : 'Pendiente' }}
                  </span>
                  <span v-if="s.puesto_nombre" class="text-xs text-gray-400 truncate">
                    📍 {{ s.puesto_nombre }}
                  </span>
                  <span v-if="s.mesa" class="text-xs text-gray-400">
                    Mesa {{ s.mesa }}
                  </span>
                </div>
              </div>

              <!-- Zona si difiere del territorio actual -->
              <div v-if="s.territorio_nombre" class="flex-shrink-0 text-right">
                <p class="text-xs text-gray-400 max-w-[80px] truncate">{{ s.territorio_nombre }}</p>
              </div>
            </div>

            <!-- Paginación -->
            <div v-if="totalPaginas > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
              <button
                :disabled="paginaActual <= 1"
                class="flex items-center gap-1 text-xs font-medium text-blue-600 disabled:text-gray-300 disabled:cursor-not-allowed"
                @click="cargar(paginaActual - 1)"
              >
                <ChevronLeftIcon class="w-4 h-4" /> Anterior
              </button>
              <span class="text-xs text-gray-500">{{ paginaActual }} / {{ totalPaginas }}</span>
              <button
                :disabled="paginaActual >= totalPaginas"
                class="flex items-center gap-1 text-xs font-medium text-blue-600 disabled:text-gray-300 disabled:cursor-not-allowed"
                @click="cargar(paginaActual + 1)"
              >
                Siguiente <ChevronRightIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Sin resultados -->
          <div v-else class="flex flex-col items-center justify-center gap-3 py-16 text-gray-400">            <UsersIcon class="w-10 h-10" />
            <p class="text-sm font-medium">Sin simpatizantes encontrados</p>
            <p v-if="busqueda" class="text-xs">Intenta con otro nombre</p>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import {
  XMarkIcon, UsersIcon, MagnifyingGlassIcon,
  ChevronLeftIcon, ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import { mapaService } from '@/services/mapaService'

const props = defineProps({
  territorioId: { type: [Number, String], required: true },
  titulo:       { type: String, default: 'Simpatizantes' },
  subtitulo:    { type: String, default: '' },
  puestoId:     { type: [Number, String], default: null },
  mesa:         { type: [Number, String], default: null },
  campanaId:    { type: [Number, String], default: 1 },
})
defineEmits(['close'])

const simpatizantes = ref([])
const loading       = ref(false)
const busqueda      = ref('')
const filtroEstado  = ref('')
const total         = ref(0)
const paginaActual  = ref(1)
const totalPaginas  = ref(1)
let   busquedaTimer = null

const totalValidados  = computed(() => simpatizantes.value.filter(s => s.estado === 'validado').length)
const totalPendientes = computed(() => simpatizantes.value.filter(s => s.estado !== 'validado').length)

async function cargar(pagina = 1) {
  loading.value = true
  paginaActual.value = pagina
  try {
    const params = {
      campana_id: props.campanaId,
      por_pagina: 20,
      pagina,
    }
    if (props.puestoId) params.puesto_id = props.puestoId
    if (props.mesa)     params.mesa      = props.mesa
    if (filtroEstado.value) params.estado = filtroEstado.value
    // Nota: búsqueda local por ahora (el endpoint aún no tiene filtro por nombre)

    const { data } = await mapaService.simpatizantesTerritorio(props.territorioId, params)
    simpatizantes.value = data.data ?? []
    total.value         = data.meta?.total ?? simpatizantes.value.length
    totalPaginas.value  = data.meta?.total_paginas ?? 1
  } catch (e) {
    simpatizantes.value = []
    console.error('Error cargando simpatizantes', e)
  } finally {
    loading.value = false
  }
}

// Filtrado local por nombre (rápido, sin llamada extra)
const simpatizantesFiltrados = computed(() => {
  if (!busqueda.value) return simpatizantes.value
  const q = busqueda.value.toLowerCase()
  return simpatizantes.value.filter(s =>
    s.nombre_completo?.toLowerCase().includes(q)
  )
})

// Reemplazar simpatizantes con los filtrados para el template

function onBuscar() {
  clearTimeout(busquedaTimer)
  busquedaTimer = setTimeout(() => cargar(1), 300)
}

onMounted(() => cargar(1))
</script>

<style scoped>
@keyframes slide-up {
  from { transform: translateY(30px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}
.animate-slide-up { animation: slide-up 0.22s ease-out; }
</style>
