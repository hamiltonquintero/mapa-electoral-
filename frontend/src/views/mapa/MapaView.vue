<template>
  <div class="relative w-full h-full overflow-hidden bg-gray-100">

    <!-- ── Mapa (100% del espacio) ─────────────────────────────── -->
    <div class="w-full h-full relative">
      <MapaInteractivo
        ref="mapaInteractivoRef"
        :hijos="mapa.hijos"
        :puestos="mapa.puestos"
        :territorio-actual="mapa.territorioActual"
        :nivel="mapa.nivel"
        :panel-open="panelOpen"
        @click-territorio="onClickTerritorio"
        @click-puesto="abrirPuesto"
      />

      <!-- Botón flotante para abrir/cerrar panel de zonas y métricas -->
      <button
        class="absolute top-3 left-3 z-[998] bg-white/95 hover:bg-white text-gray-800 text-xs font-bold py-2 px-3 rounded-full shadow-lg border border-gray-200/80 flex items-center gap-1.5 transition hover:scale-105 backdrop-blur-md cursor-pointer"
        @click="panelOpen = !panelOpen"
        :title="panelOpen ? 'Ocultar panel' : 'Ver desglose de zonas y puestos'"
      >
        <span class="text-sm font-normal">{{ panelOpen ? '✕' : '☰' }}</span>
        <span>{{ panelOpen ? 'Cerrar' : 'Zonas y Métricas' }}</span>
        <span v-if="!panelOpen && (mapa.hijos.length || mapa.puestos.length)" class="bg-blue-600 text-white text-[10px] font-bold px-1.5 py-0.2 rounded-full">
          {{ mapa.puestos.length || mapa.hijos.length }}
        </span>
      </button>

      <!-- Error banner si ocurre -->
      <div
        v-if="mapa.error"
        class="absolute top-16 left-1/2 -translate-x-1/2 z-[1000] bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg px-4 py-2 shadow"
      >
        {{ mapa.error }}
      </div>
    </div>

    <!-- ── Panel lateral flotante (Slide-Over Drawer) ──────────── -->
    <transition name="panel">
      <aside
        v-show="panelOpen"
        class="absolute top-0 left-0 bottom-0 w-84 max-w-[90vw] bg-white/95 backdrop-blur-md border-r border-gray-200/90 z-[999] shadow-2xl flex flex-col"
      >
        <!-- Cabecera del drawer con botón cerrar -->
        <div class="px-4 py-3 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
            <span class="text-xs font-bold text-gray-800 uppercase tracking-wider">Desglose Electoral</span>
          </div>
          <button
            class="w-7 h-7 rounded-full bg-gray-200/70 hover:bg-gray-300 text-gray-700 flex items-center justify-center text-xs font-bold transition cursor-pointer"
            @click="panelOpen = false"
            title="Cerrar panel lateral"
          >
            ✕
          </button>
        </div>

        <div class="flex-1 overflow-hidden flex flex-col">
          <PanelLateral
            :loading="mapa.loading"
            :territorio="mapa.territorioActual"
            :breadcrumb="mapa.breadcrumb"
            :hijos="mapa.hijos"
            :estadisticas="mapa.estadisticas"
            :puestos="mapa.puestos"
            :filtros="mapa.filtros"
            @navegar="onClickTerritorio"
            @update-filtros="mapa.setFiltros"
            @click-puesto="abrirPuesto"
            @ver-simpatizantes="abrirSimpatizantes"
          />
        </div>
      </aside>
    </transition>

    <!-- Overlay oscuro sutil cuando el drawer está abierto en pantallas pequeñas -->
    <div
      v-if="panelOpen"
      class="md:hidden absolute inset-0 bg-black/20 z-[998]"
      @click="panelOpen = false"
    />

    <!-- ── Drawer puesto 360° & Día D ────────────────────────── -->
    <PuestoDrawer
      v-if="puestoSeleccionado"
      :puesto="puestoSeleccionado"
      :filtros="mapa.filtros"
      @close="puestoSeleccionado = null"
      @ver-simpatizantes="abrirSimpatizantesPuesto"
    />

    <!-- ── Panel de simpatizantes ─────────────────────────────── -->
    <SimpatizantesPanel
      v-if="panelSimpatizantes.visible"
      :territorio-id="panelSimpatizantes.territorioId"
      :titulo="panelSimpatizantes.titulo"
      :subtitulo="panelSimpatizantes.subtitulo"
      :puesto-id="panelSimpatizantes.puestoId"
      :mesa="panelSimpatizantes.mesa"
      :campana-id="mapa.filtros.campana_id"
      @close="panelSimpatizantes.visible = false"
    />

    <!-- ── Modal Spotlight Universal (Ctrl + K) ───────────────── -->
    <SpotlightModal
      v-model="spotlightOpen"
      @select-territorio="onSpotlightTerritorio"
      @select-puesto="onSpotlightPuesto"
      @select-simpatizante="onSpotlightSimpatizante"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useMapaStore } from '@/stores/mapa'
import MapaInteractivo    from '@/components/mapa/MapaInteractivo.vue'
import PanelLateral       from '@/components/mapa/PanelLateral.vue'
import PuestoDrawer       from '@/components/mapa/PuestoDrawer.vue'
import SimpatizantesPanel from '@/components/mapa/SimpatizantesPanel.vue'
import SpotlightModal     from '@/components/mapa/SpotlightModal.vue'

const mapa                = useMapaStore()
const mapaInteractivoRef  = ref(null)
const panelOpen           = ref(false)
const spotlightOpen       = ref(false)
const puestoSeleccionado  = ref(null)

const panelSimpatizantes = reactive({
  visible:      false,
  territorioId: null,
  titulo:       '',
  subtitulo:    '',
  puestoId:     null,
  mesa:         null,
})

/* ── Navegar al territorio ─────────────────────────────────── */
function onClickTerritorio(id) {
  mapa.navegar(id)
}

/* ── Abrir panel de simpatizantes de un territorio ─────────── */
function abrirSimpatizantes(territorio) {
  panelSimpatizantes.territorioId = territorio.id
  panelSimpatizantes.titulo       = territorio.nombre
  panelSimpatizantes.subtitulo    = `${(territorio.tipo ?? '').charAt(0).toUpperCase() + (territorio.tipo ?? '').slice(1)} · ${mapa.estadisticas?.total_simpatizantes ?? 0} simpatizantes`
  panelSimpatizantes.puestoId     = null
  panelSimpatizantes.mesa         = null
  panelSimpatizantes.visible      = true
}

/* ── Abrir drawer del puesto ────────────────────────────────── */
function abrirPuesto(puesto) {
  puestoSeleccionado.value = puesto
}

/* ── Abrir simpatizantes de un puesto específico ──────────── */
function abrirSimpatizantesPuesto({ puesto, mesa }) {
  panelSimpatizantes.territorioId = puesto.territorio_id ?? mapa.territorioActual?.id
  panelSimpatizantes.titulo       = puesto.nombre
  panelSimpatizantes.subtitulo    = mesa ? `Mesa ${mesa}` : `${puesto.total_mesas || ''} mesas`
  panelSimpatizantes.puestoId     = puesto.id
  panelSimpatizantes.mesa         = mesa ?? null
  panelSimpatizantes.visible      = true
}

/* ── Manejadores de Selección Spotlight ────────────────────── */
function onSpotlightTerritorio(t) {
  if (t.id) onClickTerritorio(t.id)
}

function onSpotlightPuesto(p) {
  if (p.lat && p.lng) {
    mapaInteractivoRef.value?.flyToCoords(p.lat, p.lng, 16)
  }
  puestoSeleccionado.value = p
}

function onSpotlightSimpatizante(s) {
  if (s.puesto) {
    abrirSimpatizantesPuesto({ puesto: { id: s.puesto_id, nombre: s.puesto, total_mesas: 1 }, mesa: s.mesa })
  }
}

function handleOpenSpotlight() {
  spotlightOpen.value = true
}

function handleAbrirPuestoDesdeMapa(e) {
  const idPuesto = e.detail
  const encontrado = mapa.puestos.find(p => p.id === idPuesto)
  if (encontrado) {
    abrirPuesto(encontrado)
  }
}

onMounted(() => {
  mapa.navegar(null)
  window.addEventListener('open-spotlight', handleOpenSpotlight)
  window.addEventListener('abrir-puesto-mapa', handleAbrirPuestoDesdeMapa)
})

onUnmounted(() => {
  window.removeEventListener('open-spotlight', handleOpenSpotlight)
  window.removeEventListener('abrir-puesto-mapa', handleAbrirPuestoDesdeMapa)
})
</script>

<style scoped>
.panel-enter-active, .panel-leave-active {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
}
.panel-enter-from, .panel-leave-to {
  transform: translateX(-100%);
  opacity: 0;
}
</style>
