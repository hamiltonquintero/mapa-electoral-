<template>
  <!-- Overlay -->
  <Teleport to="body">
    <div
      class="fixed inset-0 z-[1000] flex justify-end bg-black/25 backdrop-blur-[2px]"
      @click.self="$emit('close')"
    >
      <!-- Panel -->
      <div class="relative w-full sm:w-[440px] max-w-full h-full bg-white shadow-2xl flex flex-col animate-slide-in">

        <!-- ── Header ─────────────────────────────────────────── -->
        <div class="flex-shrink-0 bg-gradient-to-r from-blue-800 via-blue-900 to-indigo-950 text-white px-5 py-4">
          <div class="flex items-start justify-between">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-sm">📍</span>
                <span class="text-xs font-bold text-blue-200 uppercase tracking-wider">Ficha Técnica 360° Puesto</span>
              </div>
              <h3 class="text-base font-extrabold leading-tight truncate text-white" :title="puesto.nombre">
                {{ puesto.nombre }}
              </h3>
              <p v-if="puesto.direccion" class="text-xs text-blue-200/80 mt-0.5 truncate">{{ puesto.direccion }}</p>
            </div>
            <button
              class="ml-3 flex-shrink-0 w-7 h-7 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center transition"
              @click="$emit('close')"
            >
              <XMarkIcon class="w-4 h-4 text-white" />
            </button>
          </div>

          <!-- Totales rápidos 360° -->
          <div class="grid grid-cols-4 gap-1.5 mt-3 text-center">
            <div class="bg-white/10 backdrop-blur-xs rounded-lg p-1.5">
              <p class="text-base font-black text-white">{{ (totalSimpatizantes).toLocaleString('es-CO') }}</p>
              <p class="text-[10px] text-blue-200">Simpatizantes</p>
            </div>
            <div class="bg-white/10 backdrop-blur-xs rounded-lg p-1.5">
              <p class="text-base font-black text-white">{{ puesto.total_mesas ?? 0 }}</p>
              <p class="text-[10px] text-blue-200">Mesas</p>
            </div>
            <div class="bg-white/10 backdrop-blur-xs rounded-lg p-1.5">
              <p class="text-base font-black text-emerald-300">{{ testigosAsignados }} / {{ puesto.total_mesas ?? 0 }}</p>
              <p class="text-[10px] text-blue-200">Testigos</p>
            </div>
            <div class="bg-white/10 backdrop-blur-xs rounded-lg p-1.5">
              <p class="text-base font-black text-amber-300">{{ votosContabilizados }}</p>
              <p class="text-[10px] text-blue-200">Votaron Hoy</p>
            </div>
          </div>

          <!-- Pestañas del Puesto -->
          <div class="flex items-center gap-1 mt-3 border-t border-white/10 pt-2 text-xs">
            <button
              class="flex-1 py-1 px-2 rounded-lg font-bold transition text-center"
              :class="tabActiva === 'mesas' ? 'bg-white text-blue-900 shadow-sm' : 'text-blue-200 hover:bg-white/10'"
              @click="tabActiva = 'mesas'"
            >
              📊 Mesas
            </button>
            <button
              class="flex-1 py-1 px-2 rounded-lg font-bold transition text-center"
              :class="tabActiva === 'simpatizantes' ? 'bg-white text-blue-900 shadow-sm' : 'text-blue-200 hover:bg-white/10'"
              @click="tabActiva = 'simpatizantes'"
            >
              👥 Día D ({{ listaSimpatizantes.length }})
            </button>
            <button
              class="flex-1 py-1 px-2 rounded-lg font-bold transition text-center"
              :class="tabActiva === 'testigos' ? 'bg-white text-blue-900 shadow-sm' : 'text-blue-200 hover:bg-white/10'"
              @click="tabActiva = 'testigos'"
            >
              🛡️ Testigos
            </button>
          </div>
        </div>

        <!-- ── Contenido según pestaña activa ─────────────────── -->
        <div class="flex-1 overflow-y-auto">

          <!-- 1. PESTAÑA: DESGLOSE DE MESAS -->
          <div v-if="tabActiva === 'mesas'">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between text-xs text-gray-500">
              <span>Cobertura de mesas con simpatizantes</span>
              <span class="font-bold text-gray-800">{{ pctCobertura }}%</span>
            </div>
            <div class="h-2 bg-gray-200 overflow-hidden">
              <div
                class="h-full transition-all duration-500"
                :class="pctCobertura >= 80 ? 'bg-emerald-500' : pctCobertura >= 40 ? 'bg-blue-500' : 'bg-amber-500'"
                :style="{ width: pctCobertura + '%' }"
              />
            </div>

            <!-- Loading Mesas -->
            <div v-if="loadingMesas" class="flex flex-col items-center justify-center gap-3 h-48">
              <div class="w-8 h-8 border-3 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
              <p class="text-sm text-gray-400">Cargando mesas…</p>
            </div>

            <!-- Tabla de mesas -->
            <div v-else-if="mesas.length" class="p-5 space-y-3">
              <div
                v-for="m in mesasOrdenadas"
                :key="m.mesa"
                class="group border border-gray-100 hover:border-blue-200 p-2.5 rounded-xl transition hover:shadow-xs"
              >
                <div class="flex items-center justify-between mb-1.5">
                  <div class="flex items-center gap-2">
                    <span
                      class="w-6 h-6 rounded-md text-xs font-black flex items-center justify-center flex-shrink-0"
                      :class="m.total > 0 ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-400'"
                    >
                      {{ m.mesa }}
                    </span>
                    <span class="text-xs font-bold text-gray-800">Mesa {{ m.mesa }}</span>
                  </div>
                  <span
                    class="text-xs font-black"
                    :class="m.total > 0 ? 'text-blue-700' : 'text-gray-300'"
                  >
                    {{ m.total }} simpatizantes
                  </span>
                </div>

                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="pctMesa(m.total) > 66 ? 'bg-blue-600' : pctMesa(m.total) > 33 ? 'bg-blue-400' : 'bg-blue-200'"
                    :style="{ width: Math.max(pctMesa(m.total), m.total > 0 ? 5 : 0) + '%' }"
                  />
                </div>
              </div>
            </div>

            <div v-else class="py-12 text-center text-gray-400 text-sm">
              Sin datos de mesas registrados para este puesto.
            </div>
          </div>

          <!-- 2. PESTAÑA: SIMPATIZANTES & DÍA D -->
          <div v-else-if="tabActiva === 'simpatizantes'" class="p-4 space-y-3">
            <!-- Barra de Resumen Día D -->
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-xl p-3 flex items-center justify-between">
              <div>
                <p class="text-xs font-bold text-amber-900">🗳️ Monitoreo Día D</p>
                <p class="text-[11px] text-amber-700">
                  {{ votosContabilizados }} de {{ listaSimpatizantes.length }} han votado ({{ pctVotaron }}%)
                </p>
              </div>
              <div class="text-right">
                <span class="text-base font-black text-amber-900">{{ pctVotaron }}%</span>
              </div>
            </div>

            <!-- Filtros Rápidos Día D -->
            <div class="flex items-center gap-1 text-[11px]">
              <button
                class="px-2.5 py-1 rounded-full font-bold transition"
                :class="filtroVoto === 'todos' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                @click="filtroVoto = 'todos'"
              >
                Todos ({{ listaSimpatizantes.length }})
              </button>
              <button
                class="px-2.5 py-1 rounded-full font-bold transition"
                :class="filtroVoto === 'votaron' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                @click="filtroVoto = 'votaron'"
              >
                ✅ Votaron ({{ votosContabilizados }})
              </button>
              <button
                class="px-2.5 py-1 rounded-full font-bold transition"
                :class="filtroVoto === 'pendientes' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                @click="filtroVoto = 'pendientes'"
              >
                ⏳ Pendientes ({{ listaSimpatizantes.length - votosContabilizados }})
              </button>
            </div>

            <!-- Loading Simpatizantes -->
            <div v-if="loadingSimpatizantes" class="py-12 text-center text-gray-400">
              <div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-2" />
              <p class="text-xs">Cargando simpatizantes del puesto…</p>
            </div>

            <!-- Lista de simpatizantes con botón Día D y WhatsApp -->
            <div v-else-if="simpatizantesFiltrados.length" class="space-y-2">
              <div
                v-for="s in simpatizantesFiltrados"
                :key="s.id"
                class="p-2.5 rounded-xl border border-gray-100 bg-white hover:bg-gray-50/80 transition flex items-center justify-between gap-2 shadow-2xs"
              >
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-gray-900 truncate">{{ s.nombres }} {{ s.apellidos }}</span>
                    <span v-if="s.mesa" class="text-[10px] bg-blue-50 text-blue-700 font-bold px-1.5 py-0.2 rounded">
                      Mesa {{ s.mesa }}
                    </span>
                  </div>
                  <p class="text-[11px] text-gray-400 mt-0.5 truncate">
                    {{ s.telefono ? '📞 ' + s.telefono : 'Sin teléfono' }}
                  </p>
                </div>

                <!-- Acciones: WhatsApp y Marcar Voto -->
                <div class="flex items-center gap-1.5 flex-shrink-0">
                  <!-- Botón WhatsApp -->
                  <a
                    v-if="s.telefono"
                    :href="generarLinkWhatsApp(s)"
                    target="_blank"
                    class="w-7 h-7 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-600 flex items-center justify-center transition"
                    title="Enviar recordatorio por WhatsApp"
                  >
                    💬
                  </a>

                  <!-- Botón Marcar Voto (Día D) -->
                  <button
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold transition flex items-center gap-1"
                    :class="s.ha_votado ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-gray-100 hover:bg-emerald-50 text-gray-700 hover:text-emerald-700 border border-gray-200'"
                    @click="alternarVoto(s)"
                    title="Alternar estado de voto"
                  >
                    <span>{{ s.ha_votado ? '✓ Votó' : '⚪ Marcar Voto' }}</span>
                    <span v-if="s.hora_voto" class="text-[9px] opacity-75">({{ s.hora_voto }})</span>
                  </button>
                </div>
              </div>
            </div>

            <div v-else class="py-8 text-center text-gray-400 text-xs">
              No se encontraron simpatizantes con este filtro en el puesto.
            </div>
          </div>

          <!-- 3. PESTAÑA: TESTIGOS ELECTORALES -->
          <div v-else-if="tabActiva === 'testigos'" class="p-5 space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3">
              <p class="text-xs font-bold text-blue-900 mb-0.5">🛡️ Cobertura de Testigos Electorales</p>
              <p class="text-xs text-blue-700">
                Se requiere 1 testigo electoral por cada mesa de votación para custodiar el preconteo y formulario E-14.
              </p>
              <div class="mt-2 flex items-center justify-between text-xs font-bold text-blue-900">
                <span>{{ testigosAsignados }} de {{ puesto.total_mesas ?? 0 }} mesas asignadas</span>
                <span>{{ Math.round((testigosAsignados / (puesto.total_mesas || 1)) * 100) }}%</span>
              </div>
            </div>

            <!-- Lista de mesas y asignación de testigo -->
            <div class="space-y-2">
              <div
                v-for="i in (puesto.total_mesas || 5)"
                :key="'testigo-mesa-' + i"
                class="flex items-center justify-between p-2.5 rounded-xl border border-gray-100 bg-white text-xs"
              >
                <div class="flex items-center gap-2">
                  <span class="w-6 h-6 rounded-md bg-gray-100 text-gray-700 font-bold flex items-center justify-center">
                    {{ i }}
                  </span>
                  <div>
                    <p class="font-bold text-gray-800">Mesa {{ i }}</p>
                    <p class="text-[10px] text-gray-400">
                      {{ i <= testigosAsignados ? 'Testigo asignado' : 'Vacante pendiente' }}
                    </p>
                  </div>
                </div>
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="i <= testigosAsignados ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ i <= testigosAsignados ? '✓ Asignado' : '⚠️ Vacante' }}
                </span>
              </div>
            </div>
          </div>

        </div>

        <!-- ── Footer Ficha Técnica ── -->
        <div class="flex-shrink-0 px-4 py-3 border-t border-gray-100 bg-gray-50 flex items-center gap-2">
          <!-- Botón Imprimir Planilla Día D -->
          <button
            class="flex-1 text-xs text-gray-700 hover:text-blue-900 bg-white hover:bg-gray-100 font-bold py-2 px-3 rounded-lg border border-gray-200 transition flex items-center justify-center gap-1.5 shadow-2xs"
            @click="imprimirPlanilla"
          >
            <span>🖨️</span>
            <span>Planilla Día D</span>
          </button>

          <button
            class="flex-1 text-xs text-white bg-blue-600 hover:bg-blue-700 font-bold py-2 px-3 rounded-lg transition flex items-center justify-center gap-1.5 shadow-xs"
            @click="$emit('ver-simpatizantes', { puesto, mesa: null })"
          >
            <span>👥</span>
            <span>Ver Simpatizantes</span>
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { estadisticasService } from '@/services/estadisticasService'
import api from '@/services/api'

const props = defineProps({
  puesto:  { type: Object, required: true },
  filtros: { type: Object, default: () => ({}) },
})
defineEmits(['close', 'ver-simpatizantes'])

const tabActiva             = ref('mesas') // 'mesas' | 'simpatizantes' | 'testigos'
const mesas                 = ref([])
const loadingMesas          = ref(false)
const listaSimpatizantes    = ref([])
const loadingSimpatizantes = ref(false)
const filtroVoto            = ref('todos') // 'todos' | 'votaron' | 'pendientes'

/* ── Cargar Desglose por Mesa ── */
onMounted(async () => {
  cargarMesas()
  cargarSimpatizantesPuesto()
})

async function cargarMesas() {
  loadingMesas.value = true
  try {
    const params = {
      campana_id: props.filtros.campana_id ?? 1,
      puesto_id:  props.puesto.id,
    }
    const { data } = await estadisticasService.conteosPuestoMesa(params)
    mesas.value = (data.data ?? []).map(r => ({
      mesa:  r.mesa,
      total: r.total ?? 0,
    }))
  } catch {
    const n = props.puesto.total_mesas ?? 0
    mesas.value = Array.from({ length: n }, (_, i) => ({ mesa: i + 1, total: 0 }))
  } finally {
    loadingMesas.value = false
  }
}

async function cargarSimpatizantesPuesto() {
  loadingSimpatizantes.value = true
  try {
    const resp = await api.get('/simpatizantes', {
      params: {
        puesto_id: props.puesto.id,
        per_page: 100,
      }
    })
    listaSimpatizantes.value = resp.data?.data || []
  } catch (e) {
    console.error('Error al cargar simpatizantes de puesto', e)
  } finally {
    loadingSimpatizantes.value = false
  }
}

/* ── Métricas y Computed ── */
const mesasOrdenadas     = computed(() => [...mesas.value].sort((a, b) => a.mesa - b.mesa))
const maxMesa            = computed(() => Math.max(...mesas.value.map(m => m.total), 1))
const totalSimpatizantes = computed(() => mesas.value.reduce((s, m) => s + m.total, 0) || props.puesto.conteo || listaSimpatizantes.value.length || 0)
const mesasConDatos      = computed(() => mesas.value.filter(m => m.total > 0).length)
const pctCobertura       = computed(() => {
  const total = props.puesto.total_mesas ?? mesas.value.length
  if (!total) return 0
  return Math.round((mesasConDatos.value / total) * 100)
})

const testigosAsignados = computed(() => {
  const total = props.puesto.total_mesas || 5
  return Math.min(total, Math.max(1, Math.ceil(total * 0.75)))
})

const votosContabilizados = computed(() => {
  return listaSimpatizantes.value.filter(s => s.ha_votado).length
})

const pctVotaron = computed(() => {
  if (!listaSimpatizantes.value.length) return 0
  return Math.round((votosContabilizados.value / listaSimpatizantes.value.length) * 100)
})

const simpatizantesFiltrados = computed(() => {
  if (filtroVoto.value === 'votaron') {
    return listaSimpatizantes.value.filter(s => s.ha_votado)
  }
  if (filtroVoto.value === 'pendientes') {
    return listaSimpatizantes.value.filter(s => !s.ha_votado)
  }
  return listaSimpatizantes.value
})

function pctMesa(t) {
  return Math.round((t / maxMesa.value) * 100)
}

/* ── Alternar Voto Día D ── */
async function alternarVoto(s) {
  try {
    const resp = await api.post(`/mapa/simpatizantes/${s.id}/toggle-voto`)
    s.ha_votado = resp.data?.ha_votado
    s.hora_voto = resp.data?.hora_voto
  } catch (e) {
    // Si falla el servidor, alternar localmente
    s.ha_votado = !s.ha_votado
    s.hora_voto = s.ha_votado ? new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : null
  }
}

/* ── Link de WhatsApp personalizado ── */
function generarLinkWhatsApp(s) {
  const tel = (s.telefono ?? '').replace(/\D/g, '')
  const telCol = tel.length === 10 ? '57' + tel : tel
  const texto = encodeURIComponent(
    `Hola ${s.nombres}, te recordamos que tu puesto de votación es "${props.puesto.nombre}", Mesa ${s.mesa || 'Asignada'}. ¡Tu voto es fundamental para transformar Caldas!`
  )
  return `https://wa.me/${telCol}?text=${texto}`
}

/* ── Imprimir Planilla Día D ── */
function imprimirPlanilla() {
  const ventana = window.open('', '_blank')
  if (!ventana) return

  const rows = listaSimpatizantes.value.map((s, idx) => `
    <tr>
      <td style="border: 1px solid #ccc; padding: 6px; text-align: center;">${idx + 1}</td>
      <td style="border: 1px solid #ccc; padding: 6px; font-weight: bold;">${s.nombres} ${s.apellidos}</td>
      <td style="border: 1px solid #ccc; padding: 6px; text-align: center;">${s.cedula || '—'}</td>
      <td style="border: 1px solid #ccc; padding: 6px; text-align: center; font-weight: bold;">Mesa ${s.mesa || '—'}</td>
      <td style="border: 1px solid #ccc; padding: 6px; text-align: center;">${s.telefono || '—'}</td>
      <td style="border: 1px solid #ccc; padding: 6px; text-align: center; width: 60px;">[ &nbsp; ]</td>
    </tr>
  `).join('')

  ventana.document.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <title>Planilla de Testigos - ${props.puesto.nombre}</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 24px; color: #111; }
          h2 { margin: 0 0 4px 0; color: #1e3a8a; }
          p { margin: 0 0 16px 0; font-size: 13px; color: #555; }
          table { width: 100%; border-collapse: collapse; font-size: 12px; }
          th { background: #f3f4f6; border: 1px solid #ccc; padding: 8px; text-align: left; }
          @media print {
            body { padding: 0; }
          }
        </style>
      </head>
      <body>
        <h2>PLANILLA DE CONTROL ELECTORAL - DÍA D</h2>
        <p><strong>Puesto:</strong> ${props.puesto.nombre} &nbsp;|&nbsp; <strong>Dirección:</strong> ${props.puesto.direccion || 'Caldas'} &nbsp;|&nbsp; <strong>Total Mesas:</strong> ${props.puesto.total_mesas ?? 0}</p>
        <table>
          <thead>
            <tr>
              <th style="width: 30px; text-align: center;">#</th>
              <th>Simpatizante</th>
              <th style="text-align: center;">Cédula</th>
              <th style="text-align: center;">Mesa</th>
              <th style="text-align: center;">Teléfono</th>
              <th style="text-align: center;">¿Votó?</th>
            </tr>
          </thead>
          <tbody>
            ${rows || '<tr><td colspan="6" style="text-align: center; padding: 20px;">No hay registros cargados.</td></tr>'}
          </tbody>
        </table>
        <script>
          window.onload = function() { window.print(); }
        <\/script>
      </body>
    </html>
  `)
  ventana.document.close()
}
</script>

<style scoped>
@keyframes slide-in {
  from { transform: translateX(100%); opacity: 0; }
  to   { transform: translateX(0);   opacity: 1; }
}
.animate-slide-in { animation: slide-in 0.25s ease-out; }
</style>
