<template>
  <div class="flex flex-col h-full bg-white overflow-hidden">

    <!-- ── Header con breadcrumb ─────────────────────────────── -->
    <div class="flex-shrink-0 px-4 pt-4 pb-3 border-b border-gray-100">
      <!-- Breadcrumb -->
      <nav class="flex flex-wrap items-center gap-1 mb-3">
        <button
          v-for="(item, i) in breadcrumbItems"
          :key="item.id ?? 'root'"
          class="flex items-center gap-1 text-xs font-medium transition-colors"
          :class="i < breadcrumbItems.length - 1
            ? 'text-blue-600 hover:text-blue-800'
            : 'text-gray-900 cursor-default pointer-events-none'"
          @click="i < breadcrumbItems.length - 1 && $emit('navegar', item.id)"
        >
          <span v-if="i > 0" class="text-gray-300 mr-1">/</span>
          <HomeIcon v-if="i === 0" class="w-3.5 h-3.5" />
          {{ item.nombre }}
        </button>
      </nav>

      <!-- Título del nivel actual -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-gray-900 leading-tight">
            {{ territorioActual?.nombre ?? 'Caldas' }}
          </h2>
          <p class="text-xs text-gray-500 capitalize mt-0.5">
            {{ nivelLabel }}
          </p>
        </div>
        <span
          class="text-xs font-semibold px-2 py-1 rounded-full"
          :class="estadisticas?.total_simpatizantes > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'"
        >
          {{ (estadisticas?.total_simpatizantes ?? 0).toLocaleString('es-CO') }} total
        </span>
      </div>
    </div>

    <!-- ── Tarjetas de estadísticas ───────────────────────────── -->
    <div v-if="estadisticas" class="flex-shrink-0 px-4 py-3 border-b border-gray-100 bg-gray-50/60">
      <div class="grid grid-cols-2 gap-2">

        <!-- Validados -->
        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm">
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs text-gray-500">Validados</span>
            <CheckCircleIcon class="w-4 h-4 text-emerald-500" />
          </div>
          <p class="text-xl font-bold text-emerald-600">
            {{ (estadisticas.total_validados ?? 0).toLocaleString('es-CO') }}
          </p>
          <div class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div
              class="h-full bg-emerald-500 rounded-full transition-all duration-500"
              :style="{ width: pctValidados + '%' }"
            />
          </div>
          <p class="text-xs text-gray-400 mt-1">{{ pctValidados }}%</p>
        </div>

        <!-- Con puesto/mesa -->
        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm">
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs text-gray-500">Ubicados</span>
            <MapPinIcon class="w-4 h-4 text-blue-500" />
          </div>
          <p class="text-xl font-bold text-blue-600">
            {{ ((estadisticas.total_simpatizantes ?? 0) - (estadisticas.sin_puesto ?? 0)).toLocaleString('es-CO') }}
          </p>
          <div class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div
              class="h-full bg-blue-500 rounded-full transition-all duration-500"
              :style="{ width: pctUbicados + '%' }"
            />
          </div>
          <p class="text-xs text-gray-400 mt-1">{{ pctUbicados }}% con puesto</p>
        </div>

        <!-- Pendientes -->
        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm">
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs text-gray-500">Pendientes</span>
            <ClockIcon class="w-4 h-4 text-amber-500" />
          </div>
          <p class="text-xl font-bold text-amber-600">
            {{ (estadisticas.total_pendientes ?? 0).toLocaleString('es-CO') }}
          </p>
        </div>

        <!-- Territorios / Puestos -->
        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm">
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs text-gray-500">{{ puestos.length ? 'Puestos' : 'Zonas' }}</span>
            <BuildingOfficeIcon v-if="puestos.length" class="w-4 h-4 text-purple-500" />
            <MapIcon v-else class="w-4 h-4 text-purple-500" />
          </div>
          <p class="text-xl font-bold text-purple-600">
            {{ puestos.length || hijos.length }}
          </p>
        </div>
      </div>
    </div>

    <!-- ── Filtros ─────────────────────────────────────────────── -->
    <FiltrosPanel
      class="flex-shrink-0 border-b border-gray-100"
      :filtros="filtros"
      @update="$emit('update-filtros', $event)"
    />

    <!-- ── Selector de pestaña si hay zonas y puestos ──────── -->
    <div v-if="hijos.length && puestos.length" class="flex border-b border-gray-100 bg-gray-50/70 p-1.5 gap-1.5 flex-shrink-0">
      <button
        class="flex-1 py-1.5 px-2 text-xs font-bold rounded-lg transition text-center"
        :class="tabActiva === 'zonas' ? 'bg-white text-blue-700 shadow-sm border border-gray-200/80' : 'text-gray-500 hover:text-gray-800'"
        @click="tabActiva = 'zonas'"
      >
        {{ hijosTitulo }} ({{ hijos.length }})
      </button>
      <button
        class="flex-1 py-1.5 px-2 text-xs font-bold rounded-lg transition text-center"
        :class="tabActiva === 'puestos' ? 'bg-white text-red-700 shadow-sm border border-gray-200/80' : 'text-gray-500 hover:text-gray-800'"
        @click="tabActiva = 'puestos'"
      >
        📍 Puestos ({{ puestos.length }})
      </button>
    </div>

    <!-- ── Lista ──────────────────────────────────────────────── -->
    <div class="flex-1 overflow-y-auto">

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center gap-3 h-40">
        <div class="w-8 h-8 border-3 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
        <p class="text-sm text-gray-400">Cargando datos…</p>
      </div>

      <!-- Hijos (municipios, comunas, veredas) -->
      <div v-else-if="hijos.length && (!puestos.length || tabActiva === 'zonas')" class="py-1">
        <div class="px-4 py-2 flex items-center justify-between">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
            {{ hijosTitulo }}
          </p>
          <p class="text-xs text-gray-400">{{ hijos.length }} zonas</p>
        </div>

        <!-- Barra de progreso total visual -->
        <div class="px-4 pb-3">
          <div class="h-2 bg-gray-100 rounded-full overflow-hidden flex">
            <div
              v-for="hijo in hijosConPct"
              :key="hijo.id"
              class="h-full transition-all duration-500"
              :style="{ width: hijo._pct + '%', background: hijo._color }"
              :title="hijo.nombre + ': ' + hijo.conteo"
            />
          </div>
          <p class="text-xs text-gray-400 mt-1">Distribución de simpatizantes por zona</p>
        </div>

        <div class="divide-y divide-gray-50">
          <button
            v-for="hijo in hijosOrdenados"
            :key="hijo.id"
            class="w-full flex items-center gap-3 px-4 py-3 hover:bg-blue-50/60 transition-colors text-left group"
            @click="$emit('navegar', hijo.id)"
          >
            <!-- Indicador color -->
            <span
              class="w-2.5 h-2.5 rounded-full flex-shrink-0 ring-2 ring-white shadow-sm"
              :style="{ background: colorPorConteo(hijo.conteo) }"
            />

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate group-hover:text-blue-700 transition-colors">
                {{ hijo.nombre }}
              </p>
              <div class="flex items-center gap-2 mt-0.5">
                <div class="flex-1 h-1 bg-gray-100 rounded-full overflow-hidden max-w-[80px]">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :style="{ width: pctDeTotal(hijo.conteo) + '%', background: colorPorConteo(hijo.conteo) }"
                  />
                </div>
                <p class="text-xs text-gray-400 capitalize">{{ hijo.tipo }}</p>
              </div>
            </div>

            <!-- Conteo + botón ver personas -->
            <div class="flex items-center gap-1 flex-shrink-0">
              <button
                v-if="(hijo.conteo ?? 0) > 0"
                class="text-xs text-blue-500 hover:text-blue-700 font-medium px-2 py-0.5 rounded-md hover:bg-blue-50 transition opacity-0 group-hover:opacity-100"
                @click.stop="$emit('ver-simpatizantes', hijo)"
              >
                👤
              </button>
              <span
                class="text-sm font-bold"
                :class="(hijo.conteo ?? 0) > 0 ? 'text-blue-700' : 'text-gray-300'"
              >
                {{ (hijo.conteo ?? 0).toLocaleString('es-CO') }}
              </span>
              <ChevronRightIcon class="w-4 h-4 text-gray-300 group-hover:text-blue-500 transition-colors" />
            </div>
          </button>
        </div>
      </div>

      <!-- Puestos de votación -->
      <div v-else-if="puestos.length && (!hijos.length || tabActiva === 'puestos')" class="py-1">
        <div class="px-4 py-2 flex items-center justify-between">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Puestos de votación</p>
          <p class="text-xs text-gray-400">{{ puestos.length }} puestos</p>
        </div>
        <div class="divide-y divide-gray-50">
          <button
            v-for="puesto in puestosOrdenados"
            :key="puesto.id"
            class="w-full flex items-center gap-3 px-4 py-3 hover:bg-red-50/50 transition-colors text-left group"
            @click="$emit('click-puesto', puesto)"
          >
            <div
              class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0"
            >
              <BuildingOfficeIcon class="w-4 h-4 text-red-600" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate group-hover:text-red-700">{{ puesto.nombre }}</p>
              <p class="text-xs text-gray-400 truncate">{{ puesto.direccion }}</p>
              <p class="text-xs text-gray-400">{{ puesto.total_mesas }} mesas · Censo: {{ Number(puesto.censo_total ?? 0).toLocaleString('es-CO') }}</p>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <span
                class="text-sm font-bold"
                :class="(puesto.conteo ?? 0) > 0 ? 'text-red-600' : 'text-gray-300'"
              >
                {{ (puesto.conteo ?? 0).toLocaleString('es-CO') }}
              </span>
              <ChevronRightIcon class="w-4 h-4 text-gray-300 group-hover:text-red-500 transition-colors" />
            </div>
          </button>
        </div>
      </div>

      <!-- Sin datos -->
      <div v-else class="flex flex-col items-center justify-center gap-3 h-48 text-gray-400">
        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
          <MapIcon class="w-6 h-6" />
        </div>
        <p class="text-sm font-medium">Sin datos para mostrar</p>
        <p class="text-xs text-center px-8">Haz clic en un municipio del mapa para explorar sus zonas</p>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  HomeIcon, CheckCircleIcon, MapPinIcon, ClockIcon,
  BuildingOfficeIcon, MapIcon, ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import FiltrosPanel from './FiltrosPanel.vue'

const props = defineProps({
  loading:          { type: Boolean, default: false },
  territorio:       { type: Object,  default: null },
  breadcrumb:       { type: Array,   default: () => [] },
  hijos:            { type: Array,   default: () => [] },
  estadisticas:     { type: Object,  default: null },
  puestos:          { type: Array,   default: () => [] },
  filtros:          { type: Object,  default: () => ({}) },
})

const emit = defineEmits(['navegar', 'update-filtros', 'click-puesto', 'ver-simpatizantes'])

const tabActiva = ref('zonas')

watch(() => props.puestos, (p) => {
  if (p.length && !props.hijos.length) {
    tabActiva.value = 'puestos'
  }
})

/* ── Computed helpers ─────────────────────────────────────── */
const territorioActual = computed(() => props.territorio)

const breadcrumbItems = computed(() => [
  { id: null, nombre: 'Caldas' },
  ...props.breadcrumb,
  ...(props.territorio ? [props.territorio] : []),
])

const nivelLabel = computed(() => {
  const tipo = props.territorio?.tipo
  const labels = {
    departamento: 'Departamento',
    municipio:    'Municipio',
    vereda:       'Vereda',
    corregimiento:'Corregimiento',
    barrio:       'Barrio / Comuna',
  }
  return labels[tipo] ?? 'Vista general'
})

const hijosTitulo = computed(() => {
  const tipo = props.hijos[0]?.tipo
  const m = {
    municipio: 'Municipios', vereda: 'Veredas',
    barrio: 'Comunas / Barrios', corregimiento: 'Corregimientos',
  }
  return m[tipo] ?? 'Territorios'
})

const totalConteo = computed(() =>
  props.hijos.reduce((s, h) => s + (h.conteo ?? 0), 0)
)

const hijosOrdenados = computed(() =>
  [...props.hijos].sort((a, b) => (b.conteo ?? 0) - (a.conteo ?? 0))
)

const hijosConPct = computed(() =>
  hijosOrdenados.value.map(h => ({
    ...h,
    _pct:   totalConteo.value > 0 ? ((h.conteo ?? 0) / totalConteo.value) * 100 : 0,
    _color: colorPorConteo(h.conteo ?? 0),
  }))
)

const puestosOrdenados = computed(() =>
  [...props.puestos].sort((a, b) => (b.conteo ?? 0) - (a.conteo ?? 0))
)

const pctValidados = computed(() => {
  const t = props.estadisticas?.total_simpatizantes ?? 0
  const v = props.estadisticas?.total_validados ?? 0
  return t > 0 ? Math.round((v / t) * 100) : 0
})

const pctUbicados = computed(() => {
  const t = props.estadisticas?.total_simpatizantes ?? 0
  const s = props.estadisticas?.sin_puesto ?? 0
  return t > 0 ? Math.round(((t - s) / t) * 100) : 0
})

/* ── Helpers de color ─────────────────────────────────────── */
function colorPorConteo(n) {
  if (!n || n === 0) return '#d1d5db'
  if (n < 20)  return '#93c5fd'
  if (n < 50)  return '#60a5fa'
  if (n < 100) return '#3b82f6'
  if (n < 200) return '#1d4ed8'
  return '#1e3a8a'
}

function pctDeTotal(n) {
  if (!totalConteo.value) return 0
  return Math.max(3, ((n ?? 0) / totalConteo.value) * 100)
}
</script>
