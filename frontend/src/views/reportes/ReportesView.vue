<template>
  <div class="p-6 max-w-7xl mx-auto space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h2 class="text-lg font-semibold text-gray-900">Reportes y tableros</h2>

      <!-- Tabs -->
      <div class="flex gap-1 bg-gray-100 p-1 rounded-lg">
        <button
          v-for="tab in tabs" :key="tab.id"
          class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
          :class="activeTab === tab.id ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
          @click="activeTab = tab.id"
        >{{ tab.label }}</button>
      </div>
    </div>

    <!-- Filtros globales -->
    <div class="card p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="label text-xs">Campaña</label>
        <select v-model="filtros.campana_id" class="input text-sm w-48" @change="cargar">
          <option value="">Todas</option>
          <option v-for="c in campanas" :key="c.id" :value="c.id">{{ c.nombre }}</option>
        </select>
      </div>
      <div v-if="activeTab === 'general'">
        <label class="label text-xs">Período (días)</label>
        <select v-model="filtros.dias" class="input text-sm w-32" @change="cargar">
          <option :value="7">7 días</option>
          <option :value="30">30 días</option>
          <option :value="60">60 días</option>
          <option :value="90">90 días</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-16"><LoadingSpinner label="Cargando datos…" /></div>

    <!-- Tablero General -->
    <template v-else-if="activeTab === 'general' && datos">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="Total apoyos"      :value="datos.totales.total_general"  color="blue"   icon="👥" />
        <StatCard label="Validados"         :value="datos.totales.validados"      color="green"  icon="✅" />
        <StatCard label="Pend. evidencia"   :value="datos.totales.pendientes"     color="yellow" icon="⏳" />
        <StatCard label="Rechazados"        :value="datos.totales.rechazados"     color="red"    icon="✗" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Evolución diaria -->
        <div class="card p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-4">Capturas por día</h3>
          <LineChart :data="evolucionChartData" :options="lineOptions" />
        </div>

        <!-- Top municipios -->
        <div class="card p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-4">Top municipios</h3>
          <div class="space-y-2">
            <div v-for="(m, i) in datos.top_municipios" :key="m.nombre" class="flex items-center gap-3">
              <span class="text-xs text-gray-400 w-4">{{ i + 1 }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-900 truncate">{{ m.nombre }}</p>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1">
                  <div class="bg-primary-500 h-1.5 rounded-full"
                    :style="{ width: pct(m.total, datos.top_municipios[0]?.total) + '%' }" />
                </div>
              </div>
              <span class="text-sm font-semibold text-gray-700 w-14 text-right">
                {{ m.total.toLocaleString('es-CO') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Productividad digitadores -->
      <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <h3 class="text-sm font-semibold text-gray-700">Productividad por digitador</h3>
        </div>
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th class="text-left px-4 py-2 font-medium text-gray-600">Usuario</th>
              <th class="text-right px-4 py-2 font-medium text-gray-600">Total</th>
              <th class="text-right px-4 py-2 font-medium text-gray-600 hidden md:table-cell">Validados</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="d in datos.por_digitador" :key="d.usuario" class="hover:bg-gray-50">
              <td class="px-4 py-2 font-medium text-gray-900">{{ d.usuario }}</td>
              <td class="px-4 py-2 text-right text-gray-700">{{ d.total.toLocaleString('es-CO') }}</td>
              <td class="px-4 py-2 text-right text-green-600 font-medium hidden md:table-cell">—</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Tablero Calidad de datos -->
    <template v-else-if="activeTab === 'calidad' && calidad">
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <StatCard label="Sin puesto votación" :value="calidad.sin_puesto_votacion"  color="yellow" icon="📍" />
        <StatCard label="Sin mesa"             :value="calidad.sin_mesa"             color="yellow" icon="🗳️" />
        <StatCard label="Sin zona asignada"    :value="calidad.sin_zona_asignada"    color="red"    icon="📍" />
        <StatCard label="Pendiente evidencia"  :value="calidad.pendiente_evidencia"  color="yellow" icon="📄" />
        <StatCard label="Rechazados"           :value="calidad.rechazados"           color="red"    icon="✗" />
        <StatCard label="% Completos"          :value="calidad.porcentaje_completos + '%'" color="green" icon="✅" />
      </div>
    </template>

    <!-- Tablero Productividad -->
    <template v-else-if="activeTab === 'productividad' && productividad">
      <div class="card overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th class="text-left px-4 py-3 font-medium text-gray-600">Digitador</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600">Total capturas</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Validados</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Último registro</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="u in productividad.por_usuario" :key="u.user_id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ u.nombre }}</td>
              <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ u.total.toLocaleString('es-CO') }}</td>
              <td class="px-4 py-3 text-right text-green-600 hidden md:table-cell">{{ u.validados }}</td>
              <td class="px-4 py-3 text-right text-gray-500 text-xs hidden lg:table-cell">
                {{ u.ultimo_registro ? dayjs(u.ultimo_registro).format('DD/MM/YYYY') : '—' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Line as LineChart } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale,
  PointElement, LineElement, Tooltip, Legend, Filler,
} from 'chart.js'
import dayjs    from 'dayjs'
import api      from '@/services/api'
import { campanaService } from '@/services/adminService'
import { useToastStore }  from '@/stores/toast'
import LoadingSpinner     from '@/components/ui/LoadingSpinner.vue'
import StatCard           from '@/components/reportes/StatCard.vue'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler)

const toasts = useToastStore()
const activeTab = ref('general')
const tabs = [
  { id: 'general',      label: 'General' },
  { id: 'calidad',      label: 'Calidad de datos' },
  { id: 'productividad',label: 'Productividad' },
]

const filtros    = reactive({ campana_id: '', dias: 30 })
const campanas   = ref([])
const loading    = ref(false)
const datos      = ref(null)
const calidad    = ref(null)
const productividad = ref(null)

const pct = (v, max) => max ? Math.round((v / max) * 100) : 0

const evolucionChartData = computed(() => ({
  labels: datos.value?.evolucion?.map(e => dayjs(e.fecha).format('DD/MM')) ?? [],
  datasets: [{
    label: 'Capturas diarias',
    data:  datos.value?.evolucion?.map(e => e.total) ?? [],
    fill:  true,
    borderColor: '#2563eb',
    backgroundColor: 'rgba(37,99,235,0.08)',
    tension: 0.3,
    pointRadius: 3,
  }],
}))

const lineOptions = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true } },
}

async function cargar() {
  loading.value = true
  try {
    const params = { campana_id: filtros.campana_id || undefined, dias: filtros.dias }

    if (activeTab.value === 'general') {
      const { data } = await api.get('/reportes/general', { params })
      datos.value = data.data
    } else if (activeTab.value === 'calidad') {
      const { data } = await api.get('/reportes/calidad-datos', { params })
      calidad.value = data.data
    } else {
      const { data } = await api.get('/reportes/productividad', { params })
      productividad.value = data.data
    }
  } catch { toasts.error('Error al cargar el reporte') }
  finally { loading.value = false }
}

watch(activeTab, cargar)

onMounted(async () => {
  try {
    const { data } = await campanaService.list()
    campanas.value = data.data ?? []
  } catch {}
  cargar()
})
</script>
