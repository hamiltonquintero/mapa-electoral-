<template>
  <div ref="wrapperRoot" class="relative w-full h-full select-none overflow-hidden" :class="{ 'dark-map': capaBase === 'oscuro' }">
    <!-- Contenedor del mapa Leaflet (100% full bleed) -->
    <div
      ref="mapContainer"
      class="w-full h-full transition-colors duration-500"
      :class="capaBase === 'vectorial' ? 'bg-gradient-to-br from-slate-100 via-gray-100 to-slate-200' : ''"
    />

    <!-- ── Barra de Herramientas Superior Derecha (Ultra Limpia y Compacta) ── -->
    <div
      class="absolute top-3 right-4 z-[997] flex items-center gap-2 pointer-events-auto"
    >
      <!-- Botón Volver a Caldas -->
      <button
        v-if="!esDepartamento"
        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg transition hover:scale-105 flex items-center gap-1.5 cursor-pointer backdrop-blur-md"
        @click="volverADepartamento"
        title="Regresar a la vista general de todo el departamento"
      >
        <span>←</span>
        <span>Volver a Caldas</span>
      </button>

      <!-- Selector de Modo Visual de Polígonos -->
      <div class="relative">
        <button
          class="bg-white/95 hover:bg-white text-gray-800 text-xs font-bold px-3 py-1.5 rounded-full transition hover:scale-105 flex items-center gap-1.5 shadow-lg border border-gray-150 backdrop-blur-md cursor-pointer"
          @click="menuModosAbierto = !menuModosAbierto"
          title="Cambiar tema visual del mapa"
        >
          <span>{{ modoColorConfig.icono }}</span>
          <span>{{ modoColorConfig.label }}</span>
          <span class="text-[9px] text-gray-400">▼</span>
        </button>

        <!-- Dropdown Modos -->
        <div
          v-show="menuModosAbierto"
          class="absolute right-0 mt-1.5 w-48 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 py-1.5 z-[1000] text-xs font-medium divide-y divide-gray-50"
        >
          <button
            class="w-full px-3 py-2 text-left flex items-center gap-2 hover:bg-blue-50 hover:text-blue-700 transition"
            :class="{ 'text-blue-600 font-bold bg-blue-50/50': modoColor === 'municipios' }"
            @click="cambiarModoColor('municipios')"
          >
            <span>🎨</span>
            <div>
              <p class="leading-tight">27 Municipios</p>
              <p class="text-[10px] text-gray-400 font-normal">Color propio para cada municipio</p>
            </div>
          </button>
          <button
            class="w-full px-3 py-2 text-left flex items-center gap-2 hover:bg-blue-50 hover:text-blue-700 transition"
            :class="{ 'text-blue-600 font-bold bg-blue-50/50': modoColor === 'subregiones' }"
            @click="cambiarModoColor('subregiones')"
          >
            <span>🏛️</span>
            <div>
              <p class="leading-tight">6 Subregiones</p>
              <p class="text-[10px] text-gray-400 font-normal">Centro Sur, Magdalena, Occidente...</p>
            </div>
          </button>
          <button
            class="w-full px-3 py-2 text-left flex items-center gap-2 hover:bg-amber-50 hover:text-amber-700 transition"
            :class="{ 'text-amber-600 font-bold bg-amber-50/50': modoColor === 'oportunidad' }"
            @click="cambiarModoColor('oportunidad')"
          >
            <span>⭐</span>
            <div>
              <p class="leading-tight">Zonas de Oro</p>
              <p class="text-[10px] text-gray-400 font-normal">Oportunidad vs Censo electoral</p>
            </div>
          </button>
          <button
            class="w-full px-3 py-2 text-left flex items-center gap-2 hover:bg-purple-50 hover:text-purple-700 transition"
            :class="{ 'text-purple-600 font-bold bg-purple-50/50': modoColor === 'simpatizantes' }"
            @click="cambiarModoColor('simpatizantes')"
          >
            <span>📊</span>
            <div>
              <p class="leading-tight">Densidad de Simpatizantes</p>
              <p class="text-[10px] text-gray-400 font-normal">Escala de calor por votantes</p>
            </div>
          </button>
        </div>
      </div>

      <!-- Toggle Pines: Estándar vs Semáforo de Metas -->
      <button
        class="px-3 py-1.5 rounded-full text-xs font-bold transition hover:scale-105 flex items-center gap-1.5 shadow-lg border backdrop-blur-md cursor-pointer"
        :class="modoPines === 'semaforo' ? 'bg-amber-50 text-amber-900 border-amber-300' : 'bg-white/95 hover:bg-white text-gray-800 border-gray-150'"
        @click="alternarModoPines"
        :title="modoPines === 'semaforo' ? 'Cambiar a pines estándar' : 'Ver semáforo de metas por puesto'"
      >
        <span>{{ modoPines === 'semaforo' ? '🚦 Semáforo ON' : '📍 Pines' }}</span>
      </button>

      <!-- Botón Minimizar/Mostrar Cuadrito Lateral -->
      <button
        class="w-8 h-8 rounded-full bg-white/95 hover:bg-white text-gray-700 flex items-center justify-center text-xs font-black shadow-lg border border-gray-150 backdrop-blur-md transition hover:scale-105 cursor-pointer"
        @click="tarjetaResumenAbierta = !tarjetaResumenAbierta"
        :title="tarjetaResumenAbierta ? 'Ocultar cuadrito lateral' : 'Ver cuadrito lateral de métricas'"
      >
        {{ tarjetaResumenAbierta ? '📊' : '📈' }}
      </button>
    </div>

    <!-- ── Cuadrito Táctico Lateral de Métricas y Desglose Electoral ── -->
    <transition name="fade-slide">
      <div
        v-if="tarjetaResumenAbierta"
        class="absolute top-14 right-4 z-[996] w-80 max-w-[calc(100vw-2rem)] bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-150/90 p-4 flex flex-col gap-3 transition-all duration-300"
      >
        <!-- Encabezado del Territorio -->
        <div class="flex items-start justify-between gap-2 border-b border-gray-100 pb-2.5">
          <div class="flex flex-col min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
              <span
                class="w-2.5 h-2.5 rounded-full flex-shrink-0 shadow-xs"
                :style="{ backgroundColor: subregionActual.color }"
              />
              <span class="text-[10px] font-black uppercase tracking-wider text-gray-500 truncate">
                {{ subregionActual.nombre }}
              </span>
              <span class="text-gray-300 text-xs">•</span>
              <span class="text-[10px] font-semibold text-gray-400">
                {{ esDepartamento ? 'Departamento' : 'Municipio' }}
              </span>
            </div>
            <h3 class="text-lg font-black text-gray-900 leading-tight truncate">
              {{ esDepartamento ? 'Caldas' : territorioActual?.nombre }}
            </h3>
          </div>

          <!-- Botón Minimizar -->
          <button
            class="w-6 h-6 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-800 flex items-center justify-center text-xs font-bold transition flex-shrink-0 cursor-pointer"
            @click="tarjetaResumenAbierta = false"
            title="Minimizar cuadrito"
          >
            ✕
          </button>
        </div>

        <!-- ── LAS 3 MÉTRICAS PRINCIPALES EN TARJETAS DESTACADAS ── -->
        <div class="flex flex-col gap-2">
          <!-- 1. TOTAL SIMPATIZANTES REGISTRADOS (Hero Card) -->
          <div class="bg-gradient-to-br from-blue-50/90 via-indigo-50/70 to-blue-50/50 border border-blue-200/90 rounded-xl p-3 flex items-center justify-between shadow-2xs">
            <div>
              <p class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Simpatizantes Registrados</p>
              <p class="text-2xl font-black text-blue-900 tracking-tight leading-none mt-1">
                {{ (simpatizantesCount || 0).toLocaleString('es-CO') }}
              </p>
              <p class="text-[10px] text-blue-500 font-medium mt-1 flex items-center gap-1">
                <span>✓ Base electoral verificada</span>
              </p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-600/10 text-blue-600 flex items-center justify-center text-xl flex-shrink-0">
              👥
            </div>
          </div>

          <!-- 2 & 3. PUESTOS DE VOTACIÓN Y MESAS HABILITADAS (Grid 2 columnas) -->
          <div class="grid grid-cols-2 gap-2">
            <!-- Puestos de Votación -->
            <div class="bg-slate-50/90 border border-slate-200/90 rounded-xl p-2.5 flex flex-col shadow-2xs">
              <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">Puestos</span>
                <span class="text-xs">📍</span>
              </div>
              <p class="text-xl font-black text-gray-900 leading-none">
                {{ puestosCountDisplay }}
              </p>
              <span class="text-[10px] text-gray-400 font-medium mt-1 truncate">
                {{ !esDepartamento && comunaFiltro ? `${puestosVisiblesCount} filtrados` : 'Geolocalizados' }}
              </span>
            </div>

            <!-- Mesas de Votación -->
            <div class="bg-slate-50/90 border border-slate-200/90 rounded-xl p-2.5 flex flex-col shadow-2xs">
              <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider">Mesas</span>
                <span class="text-xs">🗳️</span>
              </div>
              <p class="text-xl font-black text-gray-900 leading-none">
                {{ (mesasDisplay || 0).toLocaleString('es-CO') }}
              </p>
              <span class="text-[10px] text-gray-400 font-medium mt-1">Habilitadas</span>
            </div>
          </div>
        </div>

        <!-- Censo Electoral Estimado -->
        <div class="flex items-center justify-between px-2.5 py-1.5 bg-gray-50/80 rounded-lg text-xs text-gray-600 border border-gray-100">
          <span class="text-[11px] text-gray-500 font-medium">📋 Censo Electoral:</span>
          <span class="font-black text-gray-800">{{ (censoDisplay).toLocaleString('es-CO') }} votantes</span>
        </div>

        <!-- ── Desglose de Corregimientos y Zonas (Si el municipio tiene) ── -->
        <div v-if="!esDepartamento && zonasMunicipio.length" class="border-t border-gray-100 pt-2 flex flex-col gap-1.5">
          <div class="flex items-center justify-between text-xs">
            <span class="text-[11px] font-black text-gray-700">Corregimientos y Zonas ({{ zonasMunicipio.length }})</span>
            <button
              v-if="comunaFiltro"
              @click="salirDeComuna"
              class="text-[10px] text-blue-600 hover:text-blue-800 font-bold cursor-pointer"
            >
              ✕ Ver Todos
            </button>
          </div>

          <div class="max-h-44 overflow-y-auto scrollbar-none flex flex-col gap-1 pr-0.5">
            <!-- Botón Ver Todos -->
            <button
              @click="verTodosPuestos"
              class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-between cursor-pointer"
              :class="mostrarTodosPuestos && !comunaFiltro ? 'bg-blue-600 text-white shadow-2xs' : 'hover:bg-gray-100 text-gray-700 bg-gray-50/80 border border-gray-200/50'"
            >
              <span class="flex items-center gap-1.5">
                <span>🌐</span>
                <span>Todos los Puestos</span>
              </span>
              <span
                class="text-[10px] font-black px-1.5 py-0.2 rounded-full"
                :class="mostrarTodosPuestos && !comunaFiltro ? 'bg-white/25 text-white' : 'bg-gray-200 text-gray-700'"
              >
                {{ puestos.length }}
              </span>
            </button>

            <!-- Lista de Corregimientos / Zonas -->
            <button
              v-for="zona in zonasMunicipio"
              :key="zona.id"
              @click="seleccionarZona(zona)"
              class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs transition flex items-center justify-between cursor-pointer"
              :class="comunaFiltro === zona.nombreRaw ? 'bg-blue-600 text-white font-bold shadow-2xs' : 'hover:bg-blue-50/80 text-gray-700 bg-gray-50/60 border border-gray-200/60'"
            >
              <span class="truncate flex items-center gap-1.5 mr-2">
                <span class="text-xs">🏛️</span>
                <span class="truncate">{{ zona.nombre }}</span>
              </span>
              <div class="flex items-center gap-1 flex-shrink-0">
                <span
                  class="text-[10px] px-1 py-0.2 rounded font-medium"
                  :class="comunaFiltro === zona.nombreRaw ? 'text-blue-100' : 'text-gray-400'"
                >
                  {{ zona.puestos.length }} p.
                </span>
                <span
                  class="text-[10px] font-black px-1.5 py-0.2 rounded-full"
                  :class="comunaFiltro === zona.nombreRaw ? 'bg-white/25 text-white' : 'bg-blue-100 text-blue-800'"
                >
                  {{ zona.simpatizantes }}
                </span>
              </div>
            </button>
          </div>
        </div>

        <!-- Si está a nivel departamento, tip amigable -->
        <div v-else-if="esDepartamento" class="text-[11px] text-gray-400 text-center py-1 bg-gray-50/50 rounded-lg border border-dashed border-gray-200">
          Haz clic en cualquier municipio en el mapa para ver sus puestos y corregimientos.
        </div>
      </div>
    </transition>

    <!-- Pastilla flotante cuando el cuadro está minimizado -->
    <button
      v-if="!tarjetaResumenAbierta"
      class="absolute top-14 right-4 z-[996] bg-white/95 hover:bg-white text-gray-800 text-xs font-bold px-3 py-2 rounded-2xl shadow-xl border border-gray-150 backdrop-blur-md flex items-center gap-2 transition hover:scale-105 cursor-pointer"
      @click="tarjetaResumenAbierta = true"
      title="Mostrar cuadrito lateral de métricas"
    >
      <span>📊</span>
      <span class="font-black text-gray-900">{{ esDepartamento ? 'Caldas' : territorioActual?.nombre }}</span>
      <span class="text-gray-300">·</span>
      <span class="text-blue-600 font-black">{{ (simpatizantesCount || 0).toLocaleString('es-CO') }} simp.</span>
      <span class="text-gray-300">·</span>
      <span>{{ puestosCountDisplay }} puestos</span>
      <span class="text-gray-400 text-[10px]">▼</span>
    </button>

    <!-- ── Banner de Alerta Activa: Auto-Tour o GPS ── -->
    <div
      v-if="autoTourActivo || gpsActivo"
      class="absolute top-16 left-1/2 -translate-x-1/2 z-[998] pointer-events-auto animate-bounce-short"
    >
      <div
        v-if="autoTourActivo"
        class="bg-blue-900/90 text-white backdrop-blur-md px-4 py-1.5 rounded-full shadow-xl border border-blue-400/30 flex items-center gap-2.5 text-xs font-bold"
      >
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping" />
        <span>🔄 Auto-Tour Activo: Explorando Caldas</span>
        <button
          class="bg-white/20 hover:bg-white/30 text-white px-2 py-0.5 rounded-full text-[10px] ml-1 transition"
          @click="detenerAutoTour"
        >
          ✕ Detener
        </button>
      </div>

      <div
        v-else-if="gpsActivo"
        class="bg-gray-900/90 text-white backdrop-blur-md px-4 py-1.5 rounded-full shadow-xl border border-blue-500/30 flex items-center gap-2.5 text-xs font-bold"
      >
        <span class="w-2 h-2 rounded-full bg-blue-400 animate-ping" />
        <span>{{ gpsInfoTexto }}</span>
        <button
          class="bg-white/20 hover:bg-white/30 text-white px-2 py-0.5 rounded-full text-[10px] ml-1 transition"
          @click="desactivarGPS"
        >
          ✕ Salir GPS
        </button>
      </div>
    </div>

    <!-- ── Barra de Herramientas Tácticas Flotantes (Inferior Izquierda) ── -->
    <div class="absolute bottom-6 left-4 z-[997] flex flex-col gap-2">
      <!-- Selector de Capa Base (Claro / War Room / Satélite) -->
      <div class="relative">
        <button
          class="w-10 h-10 rounded-2xl bg-white/95 hover:bg-white text-gray-800 shadow-xl border border-gray-100 flex items-center justify-center text-lg transition hover:scale-105 backdrop-blur-md"
          @click="menuCapasAbierto = !menuCapasAbierto"
          title="Cambiar capa de mapa (Topográfico, Calles, Satélite, War Room)"
        >
          {{ capaBase === 'topo' ? '🗺️' : capaBase === 'calles' ? '☀️' : capaBase === 'satelite' ? '🛰️' : capaBase === 'oscuro' ? '🌙' : capaBase === 'osm' ? '🌍' : '⚡' }}
        </button>

        <div
          v-show="menuCapasAbierto"
          class="absolute left-12 bottom-0 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-2 flex items-center gap-2 z-[1000] text-xs font-bold whitespace-nowrap"
        >
          <button
            class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5"
            :class="capaBase === 'topo' ? 'bg-blue-600 text-white shadow-xs' : 'hover:bg-gray-100 text-gray-700'"
            @click="cambiarCapaBase('topo')"
            title="Relieve topográfico, carreteras, veredas, ríos y montañas de Caldas (Esri Topo)"
          >
            🗺️ Topográfico & Veredas
          </button>
          <button
            class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5"
            :class="capaBase === 'calles' ? 'bg-blue-600 text-white shadow-xs' : 'hover:bg-gray-100 text-gray-700'"
            @click="cambiarCapaBase('calles')"
            title="Mapa urbano de calles claras (Esri Streets)"
          >
            ☀️ Calles Claras
          </button>
          <button
            class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5"
            :class="capaBase === 'osm' ? 'bg-blue-600 text-white shadow-xs' : 'hover:bg-gray-100 text-gray-700'"
            @click="cambiarCapaBase('osm')"
            title="OpenStreetMap global colaborativo"
          >
            🌍 OpenStreetMap
          </button>
          <button
            class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5"
            :class="capaBase === 'satelite' ? 'bg-emerald-700 text-white shadow-xs' : 'hover:bg-gray-100 text-gray-700'"
            @click="cambiarCapaBase('satelite')"
            title="Fotografía satelital de alta resolución (Esri World Imagery)"
          >
            🛰️ Satélite
          </button>
          <button
            class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5"
            :class="capaBase === 'oscuro' ? 'bg-gray-900 text-white shadow-xs' : 'hover:bg-gray-100 text-gray-700'"
            @click="cambiarCapaBase('oscuro')"
            title="Modo táctico oscuro War Room"
          >
            🌙 War Room
          </button>
          <button
            class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 font-bold"
            :class="capaBase === 'vectorial' ? 'bg-blue-600 text-white shadow-xs' : 'hover:bg-gray-100 text-gray-700'"
            @click="cambiarCapaBase('vectorial')"
            title="Solo polígonos vectoriales sin mapa base"
          >
            ⚡ Minimalista
          </button>
        </div>
      </div>

      <!-- Botón Modo Líder en Territorio (GPS en Vivo) -->
      <button
        class="w-10 h-10 rounded-2xl bg-white/95 hover:bg-white shadow-xl border border-gray-100 flex items-center justify-center text-lg transition hover:scale-105 backdrop-blur-md"
        :class="{ 'ring-2 ring-blue-500 bg-blue-50': gpsActivo }"
        @click="toggleGPS"
        title="Mi Ubicación GPS (Modo Líder en Territorio)"
      >
        📍
      </button>

      <!-- Botón Auto-Tour -->
      <button
        class="w-10 h-10 rounded-2xl bg-white/95 hover:bg-white shadow-xl border border-gray-100 flex items-center justify-center text-lg transition hover:scale-105 backdrop-blur-md"
        :class="{ 'ring-2 ring-emerald-500 bg-emerald-50': autoTourActivo }"
        @click="toggleAutoTour"
        title="Auto-Tour Cinemático por Caldas"
      >
        🔄
      </button>

      <!-- Botón Pantalla Completa / War Room -->
      <button
        class="w-10 h-10 rounded-2xl bg-white/95 hover:bg-white text-gray-800 shadow-xl border border-gray-100 flex items-center justify-center text-base font-bold transition hover:scale-105 backdrop-blur-md"
        @click="togglePantallaCompleta"
        title="Modo Pantalla Completa / Presentación"
      >
        ⛶
      </button>
    </div>

    <!-- ── Leyenda Compacta Colapsible (Esquina Inferior Derecha) ── -->
    <div class="absolute bottom-6 right-4 z-[997]">
      <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-xl border border-gray-100 transition-all duration-200 overflow-hidden min-w-[200px]">
        <!-- Cabecera de la Leyenda -->
        <button
          class="w-full px-3.5 py-2 flex items-center justify-between gap-3 text-xs font-bold text-gray-800 hover:bg-gray-50/80 transition"
          @click="leyendaAbierta = !leyendaAbierta"
        >
          <span class="flex items-center gap-1.5">
            <span>{{ modoColorConfig.icono }}</span>
            <span>{{ modoPines === 'semaforo' ? 'Semáforo de Metas' : modoColorConfig.tituloLeyenda }}</span>
          </span>
          <span class="text-[10px] text-gray-400 font-normal">
            {{ leyendaAbierta ? '▲ Ocultar' : '▼ Ver' }}
          </span>
        </button>

        <!-- Contenido expandido de la leyenda -->
        <div v-show="leyendaAbierta" class="p-3 pt-0 border-t border-gray-100 text-[11px]">
          <!-- Leyenda Semáforo de Metas -->
          <div v-if="modoPines === 'semaforo'">
            <p class="text-[10px] text-gray-400 mb-1.5">Cumplimiento de meta por puesto:</p>
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500 shadow-xs" />
                <span class="text-gray-700 font-medium">≥ 75% Meta Alcanzada</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-500 shadow-xs" />
                <span class="text-gray-700 font-medium">35% – 74% En Progreso</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500 shadow-xs" />
                <span class="text-gray-700 font-medium">&lt; 35% Crítico / Atención</span>
              </div>
            </div>
          </div>

          <!-- Leyenda 6 Subregiones -->
          <div v-else-if="modoColor === 'subregiones'">
            <p class="text-[10px] text-gray-400 mb-1.5">Las 6 Subregiones de Caldas:</p>
            <div class="space-y-1">
              <div v-for="sub in listaSubregiones" :key="sub.nombre" class="flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                  <span class="w-2.5 h-2.5 rounded-full shadow-xs" :style="{ backgroundColor: sub.color }" />
                  <span class="text-gray-800 font-medium">{{ sub.nombre }}</span>
                </div>
                <span class="text-gray-400 text-[10px]">{{ sub.municipios.length }} mpios</span>
              </div>
            </div>
          </div>

          <!-- Leyenda Zonas de Oro / Oportunidad -->
          <div v-else-if="modoColor === 'oportunidad'">
            <p class="text-[10px] text-gray-400 mb-1.5">Potencial Electoral (Censo vs Simpatizantes):</p>
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-sm bg-amber-400 shadow-xs" />
                <span class="text-gray-700 font-bold">⭐ Zona de Oro (Alto Potencial)</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-sm bg-blue-500 shadow-xs" />
                <span class="text-gray-700 font-medium">Oportunidad Media</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-sm bg-emerald-500 shadow-xs" />
                <span class="text-gray-700 font-medium">🛡️ Zona Blindada (Fuerte)</span>
              </div>
            </div>
          </div>

          <!-- Leyenda 27 Municipios -->
          <div v-else-if="modoColor === 'municipios' && esDepartamento">
            <p class="text-[10px] text-gray-400 mb-1.5">Clic en cualquier municipio para explorarlo:</p>
            <div class="grid grid-cols-2 gap-x-2 gap-y-1 max-h-36 overflow-y-auto pr-1 scrollbar-thin">
              <div
                v-for="m in listaMunicipiosColor"
                :key="m.nombre"
                class="flex items-center gap-1.5 cursor-pointer hover:bg-gray-50 p-0.5 rounded transition"
                @click="zoomAMunicipioPorNombre(m.nombre)"
              >
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 shadow-2xs" :style="{ backgroundColor: m.color }" />
                <span class="text-gray-700 truncate" :title="m.nombre">{{ m.nombre }}</span>
              </div>
            </div>
          </div>

          <!-- Leyenda Calor -->
          <div v-else>
            <div v-for="item in leyenda" :key="item.label" class="flex items-center gap-2 mb-1">
              <span class="w-3.5 h-2.5 rounded-xs flex-shrink-0 border border-black/10" :style="{ background: item.color }" />
              <span class="text-gray-600">{{ item.label }}</span>
            </div>
          </div>

          <div v-if="puestosVisiblesCount > 0" class="mt-2 pt-1.5 border-t border-gray-100 flex items-center justify-between text-[11px]">
            <span class="text-gray-600 font-medium flex items-center gap-1">📍 Puestos activos:</span>
            <span class="text-blue-700 font-bold">{{ puestosVisiblesCount }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'
import 'leaflet.markercluster'

const props = defineProps({
  hijos:            { type: Array,   default: () => [] },
  puestos:          { type: Array,   default: () => [] },
  territorioActual: { type: Object,  default: null },
  nivel:            { type: String,  default: 'departamento' },
  panelOpen:        { type: Boolean, default: false },
})
const emit = defineEmits(['click-territorio', 'click-puesto'])

const wrapperRoot          = ref(null)
const mapContainer         = ref(null)
const capaBase             = ref('topo') // 'topo' | 'calles' | 'osm' | 'satelite' | 'oscuro' | 'vectorial'
const modoColor            = ref('municipios') // 'municipios' | 'subregiones' | 'oportunidad' | 'simpatizantes'
const modoPines            = ref('estandar') // 'estandar' | 'semaforo'
const comunaFiltro         = ref(null)
const leyendaAbierta       = ref(false)
const menuModosAbierto     = ref(false)
const menuCapasAbierto     = ref(false)
const puestosVisiblesCount = ref(0)
const tarjetaResumenAbierta = ref(true)

const MODOS_COLOR = {
  municipios: {
    icono: '🎨',
    label: '27 Municipios',
    tituloLeyenda: 'Leyenda de Municipios',
  },
  subregiones: {
    icono: '🏛️',
    label: '6 Subregiones',
    tituloLeyenda: 'Subregiones de Caldas',
  },
  oportunidad: {
    icono: '⭐',
    label: 'Zonas de Oro',
    tituloLeyenda: 'Potencial Electoral',
  },
  simpatizantes: {
    icono: '📊',
    label: 'Densidad Simpatizantes',
    tituloLeyenda: 'Densidad de Simpatizantes',
  },
}

const modoColorConfig = computed(() => {
  return MODOS_COLOR[modoColor.value] || MODOS_COLOR.municipios
})

const autoTourActivo       = ref(false)
const gpsActivo            = ref(false)
const gpsInfoTexto         = ref('')

let map              = null
let baseTileLayer    = null
let geojsonLayer     = null
let clusterLayer     = null
let caldas           = null
let userGpsMarker    = null
let userGpsCircle    = null
let autoTourTimer    = null
let autoTourPaso     = 0

/* ── Proveedores de Capa Base (Ultra Rápidos, Gratuitos y Sin Restricciones) ── */
const TILE_LAYERS = {
  topo: {
    nombre: '🗺️ Relieve Topográfico & Veredas (Esri Topo)',
    url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
    attr: '&copy; Esri, DeLorme, USGS, NPS',
    maxZoom: 18,
  },
  calles: {
    nombre: '☀️ Calles y Ciudades (Esri)',
    url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
    attr: '&copy; Esri, HERE, Garmin',
    maxZoom: 19,
  },
  osm: {
    nombre: '🌍 OpenStreetMap',
    url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
    attr: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  },
  satelite: {
    nombre: '🛰️ Fotografía Satelital Real',
    url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    attr: '&copy; Esri, Maxar, Earthstar Geographics',
    maxZoom: 19,
  },
  oscuro: {
    nombre: '🌙 Táctico War Room',
    url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
    attr: '&copy; OpenStreetMap &copy; CARTO',
    subdomains: 'abcd',
    maxZoom: 19,
  },
  vectorial: {
    nombre: '⚡ Modo Minimalista (Solo Polígonos)',
    url: null,
    attr: 'Mapa Vectorial Caldas',
  },
}

/* ── 6 Subregiones Oficiales de Caldas ───────────────────────── */
const SUBREGIONES = {
  'centro sur': {
    nombre: 'Centro Sur',
    color: '#3b82f6', // Azul
    municipios: ['manizales', 'villamaria', 'chinchina', 'palestina', 'neira']
  },
  'magdalena caldense': {
    nombre: 'Magdalena Caldense',
    color: '#06b6d4', // Cian
    municipios: ['la dorada', 'norcasia', 'samana', 'victoria']
  },
  'bajo occidente': {
    nombre: 'Bajo Occidente',
    color: '#f59e0b', // Ámbar
    municipios: ['anserma', 'belalcazar', 'risaralda', 'san jose', 'viterbo']
  },
  'alto occidente': {
    nombre: 'Alto Occidente',
    color: '#ec4899', // Rosa
    municipios: ['filadelfia', 'la merced', 'marmato', 'riosucio', 'supia']
  },
  'alto oriente': {
    nombre: 'Alto Oriente',
    color: '#10b981', // Esmeralda
    municipios: ['manzanares', 'marquetalia', 'marulanda', 'pensilvania']
  },
  'norte': {
    nombre: 'Norte',
    color: '#8b5cf6', // Violeta
    municipios: ['aguadas', 'aranzazu', 'pacora', 'salamina']
  }
}

const listaSubregiones = Object.values(SUBREGIONES)

function getSubregionDeMunicipio(nombreMuni) {
  const n = norm(nombreMuni)
  for (const [key, sub] of Object.entries(SUBREGIONES)) {
    if (sub.municipios.includes(n)) return sub
  }
  return { nombre: 'Caldas', color: '#6366f1' }
}

/* ── 27 Colores Armónicos para Municipios ───────────────────── */
const MUNICIPIO_COLORES = {
  'aguadas':     '#6366f1',
  'anserma':     '#0ea5e9',
  'aranzazu':    '#10b981',
  'belalcazar':  '#f59e0b',
  'chinchina':   '#8b5cf6',
  'filadelfia':  '#ec4899',
  'la dorada':   '#06b6d4',
  'la merced':   '#84cc16',
  'manizales':   '#2563eb',
  'manzanares':  '#f97316',
  'marmato':     '#14b8a6',
  'marquetalia': '#d946ef',
  'marulanda':   '#eab308',
  'neira':       '#0284c7',
  'norcasia':    '#10b981',
  'pacora':      '#a855f7',
  'palestina':   '#e11d48',
  'pensilvania': '#22c55e',
  'riosucio':    '#ef4444',
  'risaralda':   '#059669',
  'salamina':    '#9333ea',
  'samana':      '#1d4ed8',
  'san jose':    '#d97706',
  'supia':       '#7c3aed',
  'victoria':    '#0284c7',
  'villamaria':  '#16a34a',
  'viterbo':     '#db2777',
}

const PALETA_FALLBACK = [
  '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4',
  '#84cc16', '#f97316', '#6366f1', '#14b8a6', '#d946ef', '#eab308', '#0284c7',
  '#22c55e', '#a855f7', '#e11d48', '#059669', '#f43f5e', '#7c3aed', '#2563eb',
]

function norm(s) {
  return (s ?? '').toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9 ]/g, '').trim()
}

function getColorMunicipio(nombre, index = 0) {
  const n = norm(nombre)
  return MUNICIPIO_COLORES[n] || PALETA_FALLBACK[index % PALETA_FALLBACK.length]
}

/* ── Escala de Calor para Simpatizantes ─────────────────────── */
const leyenda = [
  { color: '#f3f4f6', label: 'Sin datos' },
  { color: '#dbeafe', label: '1 – 19' },
  { color: '#93c5fd', label: '20 – 49' },
  { color: '#3b82f6', label: '50 – 99' },
  { color: '#1d4ed8', label: '100 – 199' },
  { color: '#1e3a8a', label: '200 +' },
]

function getColorDensidad(conteo) {
  if (!conteo || conteo === 0) return '#f3f4f6'
  if (conteo < 20)  return '#dbeafe'
  if (conteo < 50)  return '#93c5fd'
  if (conteo < 100) return '#3b82f6'
  if (conteo < 200) return '#1d4ed8'
  return '#1e3a8a'
}

/* ── Zonas de Oro / Oportunidad Electoral ───────────────────── */
function getOportunidadInfo(nombre, conteo) {
  const n = norm(nombre)
  // Censo electoral aproximado por municipio
  const censoMap = {
    'manizales': 348633, 'la dorada': 62140, 'chinchina': 44920,
    'villamaria': 42800, 'anserma': 29400, 'riosucio': 33100,
    'salamina': 15800, 'pensilvania': 18500, 'supia': 22300,
    'aguadas': 17600, 'neira': 18900, 'samana': 16400,
  }
  const censo = censoMap[n] || 9500
  const tasa = (conteo / censo) * 1000

  if (censo >= 20000 && tasa < 2.0) {
    return {
      categoria: 'Zona de Oro (Alto Potencial)',
      color: '#f59e0b', // Ámbar Oro
      censo,
      tasa: tasa.toFixed(1),
    }
  } else if (tasa >= 4.0) {
    return {
      categoria: 'Zona Blindada (Fuerte)',
      color: '#10b981', // Esmeralda
      censo,
      tasa: tasa.toFixed(1),
    }
  } else {
    return {
      categoria: 'Oportunidad Media',
      color: '#3b82f6', // Azul
      censo,
      tasa: tasa.toFixed(1),
    }
  }
}

/* ── Computed State ────────────────────────────────────────── */
const esDepartamento = computed(() => !props.territorioActual || props.territorioActual.tipo === 'departamento')
const esManizales    = computed(() => norm(props.territorioActual?.nombre) === 'manizales')

const simpatizantesCount = computed(() => {
  if (props.territorioActual && !esDepartamento.value) {
    const direct = props.territorioActual.conteo ?? props.territorioActual.total_simpatizantes
    if (direct !== undefined && direct !== null && direct > 0) return direct

    const porPuestos = props.puestos.reduce((s, p) => s + (p.conteo ?? p.simpatizantes_count ?? 0), 0)
    if (porPuestos > 0) return porPuestos

    const porHijos = props.hijos.reduce((s, h) => s + (h.conteo || 0), 0)
    if (porHijos > 0) return porHijos

    return direct ?? 0
  }
  return props.hijos.reduce((s, h) => s + (h.conteo || 0), 0) || 1081
})

const colorMunicipioActual = computed(() => {
  if (!props.territorioActual) return null
  return getColorMunicipio(props.territorioActual.nombre)
})

const subregionActual = computed(() => {
  if (esDepartamento.value) {
    return { nombre: 'Departamento de Caldas', color: '#2563eb' }
  }
  return getSubregionDeMunicipio(props.territorioActual?.nombre || '')
})

const censoDisplay = computed(() => {
  if (esManizales.value) return 348633
  if (esDepartamento.value) return 828671
  return Math.round((simpatizantesCount.value || 50) * 120)
})
const puestosCountDisplay = computed(() => {
  if (esManizales.value) return 185
  if (esDepartamento.value) return 339
  return props.puestos.length || 12
})
const mesasDisplay = computed(() => {
  if (esManizales.value) return 2061
  if (esDepartamento.value) return 2492
  return props.puestos.reduce((s, p) => s + (p.total_mesas || 0), 0) || 45
})

const mostrarTodosPuestos = ref(true)

function limpiarNombreComuna(raw) {
  if (!raw) return 'Sector General'
  let s = String(raw).trim()
  return s
    .replace(/^(\d+\s*[-:]?\s*|comuna\s*\d*\s*[-:]?\s*)/i, '')
    .replace(/\b\w/g, l => l.toUpperCase())
    .trim() || s
}

const zonasMunicipio = computed(() => {
  if (esDepartamento.value) return []

  // 1. Si los puestos tienen comuna (como Manizales)
  const tieneComunas = props.puestos.some(p => !!p.comuna)
  if (tieneComunas) {
    const mapZonas = {}
    props.puestos.forEach(p => {
      const k = p.comuna || 'Otras Zonas'
      if (!mapZonas[k]) {
        mapZonas[k] = {
          id: k,
          nombre: limpiarNombreComuna(k),
          nombreRaw: k,
          puestos: [],
          simpatizantes: 0,
          mesas: 0,
        }
      }
      mapZonas[k].puestos.push(p)
      mapZonas[k].simpatizantes += (p.conteo ?? p.simpatizantes_count ?? 0)
      mapZonas[k].mesas += (p.total_mesas ?? 1)
    })
    return Object.values(mapZonas).sort((a, b) => a.nombre.localeCompare(b.nombre))
  }

  // 2. Si el municipio tiene hijos en props.hijos (veredas o barrios)
  if (props.hijos && props.hijos.length > 0) {
    return props.hijos.map(h => ({
      id: h.id,
      nombre: limpiarNombreComuna(h.nombre),
      nombreRaw: h.nombre,
      puestos: props.puestos.filter(p => p.territorio_id === h.id),
      simpatizantes: h.conteo || 0,
      mesas: 0,
    }))
  }

  // 3. Si tiene zonas registradas en puestos
  const tieneZonas = props.puestos.some(p => !!p.zona)
  if (tieneZonas) {
    const mapZ = {}
    props.puestos.forEach(p => {
      const z = p.zona || 'Sector General'
      if (!mapZ[z]) {
        mapZ[z] = {
          id: z,
          nombre: `Zona ${z}`,
          nombreRaw: z,
          puestos: [],
          simpatizantes: 0,
          mesas: 0,
        }
      }
      mapZ[z].puestos.push(p)
      mapZ[z].simpatizantes += (p.conteo ?? p.simpatizantes_count ?? 0)
      mapZ[z].mesas += (p.total_mesas ?? 1)
    })
    return Object.values(mapZ).sort((a, b) => a.nombre.localeCompare(b.nombre))
  }

  return []
})

const zonaActivaInfo = computed(() => {
  if (!comunaFiltro.value) return null
  return zonasMunicipio.value.find(z => z.nombreRaw === comunaFiltro.value || z.nombre === comunaFiltro.value || z.id === comunaFiltro.value)
})

function seleccionarZona(zona) {
  comunaFiltro.value = zona.nombreRaw
  mostrarTodosPuestos.value = false
  renderPuestos()

  if (zona.puestos && zona.puestos.length) {
    const pts = zona.puestos.filter(p => p.latitud && p.longitud).map(p => [+p.latitud, +p.longitud])
    if (pts.length > 0) {
      const bounds = L.latLngBounds(pts)
      if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [60, 60], maxZoom: 16, animate: true, duration: 1 })
      }
    }
  }
}

function verTodosPuestos() {
  comunaFiltro.value = null
  mostrarTodosPuestos.value = true
  renderPuestos()
  if (props.puestos.length) {
    const pts = props.puestos.filter(p => p.latitud && p.longitud).map(p => [+p.latitud, +p.longitud])
    if (pts.length > 0) {
      const bounds = L.latLngBounds(pts)
      if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [50, 50], animate: true, duration: 1 })
      }
    }
  }
}

function salirDeComuna() {
  comunaFiltro.value = null
  mostrarTodosPuestos.value = false
  renderPuestos()
  if (geojsonLayer && props.territorioActual) {
    geojsonLayer.eachLayer(layer => {
      if (norm(layer.feature?.properties?._nombre) === norm(props.territorioActual.nombre)) {
        map.fitBounds(layer.getBounds(), { padding: [40, 40], animate: true, duration: 1 })
      }
    })
  }
}

function filtrarPorComuna(comuna) {
  comunaFiltro.value = comuna
  mostrarTodosPuestos.value = false
  renderPuestos()
}

function cambiarModoColor(modo) {
  modoColor.value = modo
  menuModosAbierto.value = false
  renderMunicipios()
}

function alternarModoPines() {
  modoPines.value = modoPines.value === 'semaforo' ? 'estandar' : 'semaforo'
  renderPuestos()
}

function volverADepartamento() {
  detenerAutoTour()
  emit('click-territorio', 1)
}

function zoomAMunicipioPorNombre(nombre) {
  if (!geojsonLayer) return
  geojsonLayer.eachLayer(layer => {
    if (norm(layer.feature?.properties?._nombre) === norm(nombre)) {
      map.fitBounds(layer.getBounds(), { padding: [50, 50], maxZoom: 13, animate: true, duration: 1 })
      if (layer.feature.properties._id) {
        emit('click-territorio', layer.feature.properties._id)
      }
    }
  })
}

/* ── Cambio de Capa Base (Vectorial Nativo / Claro / Oscuro / Satélite) ── */
function cambiarCapaBase(capa) {
  capaBase.value = capa
  menuCapasAbierto.value = false
  if (baseTileLayer) {
    baseTileLayer.remove()
    baseTileLayer = null
  }
  const config = TILE_LAYERS[capa]
  if (capa !== 'vectorial' && config?.url) {
    const opts = {
      attribution: config.attr,
      maxZoom: config.maxZoom || 19,
    }
    if (config.subdomains) opts.subdomains = config.subdomains
    baseTileLayer = L.tileLayer(config.url, opts).addTo(map)
  }
  renderMunicipios()
}

/* ── Modo Líder en Territorio: GPS en Vivo ──────────────────── */
function toggleGPS() {
  if (gpsActivo.value) {
    desactivarGPS()
  } else {
    activarGPS()
  }
}

function activarGPS() {
  if (!navigator.geolocation) {
    alert('Tu navegador no soporta geolocalización')
    return
  }

  gpsActivo.value = true
  gpsInfoTexto.value = 'Localizando tu posición en territorio…'

  navigator.geolocation.getCurrentPosition(
    pos => {
      const lat = pos.coords.latitude
      const lng = pos.coords.longitude

      // Si está en o cerca de Colombia
      map.flyTo([lat, lng], 15, { duration: 1.5 })

      // Marcador de usuario con pulso radar
      if (userGpsMarker) userGpsMarker.remove()
      if (userGpsCircle) userGpsCircle.remove()

      userGpsCircle = L.circle([lat, lng], {
        radius: 500,
        color: '#2563eb',
        fillColor: '#3b82f6',
        fillOpacity: 0.12,
        weight: 1.5,
      }).addTo(map)

      const gpsIcon = L.divIcon({
        className: 'user-gps-dot',
        html: `<div class="relative w-5 h-5 flex items-center justify-center">
                 <div class="absolute inset-0 rounded-full bg-blue-500 animate-ping opacity-75"></div>
                 <div class="w-3.5 h-3.5 rounded-full bg-blue-600 border-2 border-white shadow-md"></div>
               </div>`,
        iconSize: [20, 20],
        iconAnchor: [10, 10],
      })
      userGpsMarker = L.marker([lat, lng], { icon: gpsIcon }).addTo(map)

      // Encontrar puesto más cercano
      let puestoCercano = null
      let menorDist = Infinity

      props.puestos.forEach(p => {
        if (p.latitud && p.longitud) {
          const dist = calcularDistancia(lat, lng, +p.latitud, +p.longitud)
          if (dist < menorDist) {
            menorDist = dist
            puestoCercano = p
          }
        }
      })

      if (puestoCercano) {
        const distStr = menorDist < 1 ? `${Math.round(menorDist * 1000)}m` : `${menorDist.toFixed(1)}km`
        gpsInfoTexto.value = `📍 Estás a ${distStr} de: ${puestoCercano.nombre}`
      } else {
        gpsInfoTexto.value = `📍 Ubicación detectada en [${lat.toFixed(3)}, ${lng.toFixed(3)}]`
      }
    },
    err => {
      console.warn('GPS no disponible o denegado', err)
      // Simular ubicación en el centro de Manizales para demostración si se deniega
      map.flyTo([5.068, -75.517], 15, { duration: 1.2 })
      gpsInfoTexto.value = '📍 Simulación GPS activa: Manizales Centro'
    },
    { enableHighAccuracy: true, timeout: 8000 }
  )
}

function desactivarGPS() {
  gpsActivo.value = false
  userGpsMarker?.remove()
  userGpsCircle?.remove()
  userGpsMarker = null
  userGpsCircle = null
}

function calcularDistancia(lat1, lon1, lat2, lon2) {
  const R = 6371 // Radio de la Tierra en km
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLon = (lon2 - lon1) * Math.PI / 180
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2)
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
}

/* ── Auto-Tour Cinemático por Caldas ───────────────────────── */
const TOUR_PARADAS = [
  { nombre: 'Manizales (Centro Sur)', coords: [5.068, -75.517], zoom: 13 },
  { nombre: 'Chinchiná (Centro Sur)', coords: [4.982, -75.603], zoom: 13 },
  { nombre: 'La Dorada (Magdalena Caldense)', coords: [5.454, -74.663], zoom: 13 },
  { nombre: 'Riosucio (Alto Occidente)', coords: [5.421, -75.703], zoom: 13 },
  { nombre: 'Salamina (Norte)', coords: [5.405, -75.488], zoom: 13 },
  { nombre: 'Pensilvania (Alto Oriente)', coords: [5.383, -75.161], zoom: 13 },
  { nombre: 'Todo el Departamento de Caldas', coords: [5.25, -75.45], zoom: 9 },
]

function toggleAutoTour() {
  if (autoTourActivo.value) {
    detenerAutoTour()
  } else {
    iniciarAutoTour()
  }
}

function iniciarAutoTour() {
  autoTourActivo.value = true
  autoTourPaso = 0
  ejecutarPasoTour()
  autoTourTimer = setInterval(() => {
    autoTourPaso = (autoTourPaso + 1) % TOUR_PARADAS.length
    ejecutarPasoTour()
  }, 10000)
}

function ejecutarPasoTour() {
  if (!map) return
  const parada = TOUR_PARADAS[autoTourPaso]
  map.flyTo(parada.coords, parada.zoom, { duration: 2, easeLinearity: 0.25 })
}

function detenerAutoTour() {
  autoTourActivo.value = false
  clearInterval(autoTourTimer)
  autoTourTimer = null
}

/* ── Pantalla Completa / Presentación ──────────────────────── */
function togglePantallaCompleta() {
  if (!document.fullscreenElement) {
    wrapperRoot.value?.requestFullscreen?.().catch(() => {})
  } else {
    document.exitFullscreen?.().catch(() => {})
  }
}

/* ── Init Mapa Leaflet ─────────────────────────────────────── */
onMounted(async () => {
  map = L.map(mapContainer.value, {
    center: [5.25, -75.45],
    zoom: 9,
    minZoom: 8,
    maxZoom: 19,
    zoomControl: false,
    preferCanvas: true, // Aceleración por hardware GPU para 60 FPS
  })

  // Control zoom abajo a la izquierda
  L.control.zoom({ position: 'bottomleft' }).addTo(map)

  // Capa base solo si se seleccionó una capa online
  const configBase = TILE_LAYERS[capaBase.value]
  if (capaBase.value !== 'vectorial' && configBase?.url) {
    const opts = {
      attribution: configBase.attr,
      maxZoom: configBase.maxZoom || 19,
    }
    if (configBase.subdomains) opts.subdomains = configBase.subdomains
    baseTileLayer = L.tileLayer(configBase.url, opts).addTo(map)
  }

  try {
    const r = await fetch('/geojson/caldas_municipios.geojson')
    caldas = await r.json()
    renderMunicipios()
  } catch (e) {
    console.error('GeoJSON Caldas no disponible', e)
  }
})

onUnmounted(() => {
  detenerAutoTour()
  map?.remove()
})

/* ── Render de Municipios con Hover Peek Enriquecido ────────── */
function renderMunicipios() {
  if (!caldas || !map) return

  const byCode = {}, byName = {}
  props.hijos.forEach(h => {
    if (h.codigo) byCode[h.codigo] = h
    byName[norm(h.nombre)] = h
  })

  const enriched = {
    ...caldas,
    features: caldas.features.map((f, idx) => {
      const m = byCode[f.properties.codigo_divipola] || byName[norm(f.properties.nombre)]
      const nombre = m?.nombre ?? f.properties.nombre
      const subregion = getSubregionDeMunicipio(nombre)
      const opInfo = getOportunidadInfo(nombre, m?.conteo ?? 0)

      return {
        ...f,
        properties: {
          ...f.properties,
          _id:          m?.id     ?? null,
          _nombre:      nombre,
          _conteo:      m?.conteo ?? 0,
          _colorMuni:   getColorMunicipio(nombre, idx),
          _subregion:   subregion,
          _oportunidad: opInfo,
        },
      }
    }),
  }

  geojsonLayer?.remove()
  geojsonLayer = L.geoJSON(enriched, {
    style: f => {
      const p = f.properties
      const isMuniActive = props.territorioActual && (p._id === props.territorioActual.id || norm(p._nombre) === norm(props.territorioActual.nombre))

      // Colores de borde y relleno según modo activo
      let fillColor = p._colorMuni
      if (modoColor.value === 'subregiones') {
        fillColor = p._subregion?.color || '#3b82f6'
      } else if (modoColor.value === 'oportunidad') {
        fillColor = p._oportunidad?.color || '#3b82f6'
      } else if (modoColor.value === 'simpatizantes') {
        fillColor = getColorDensidad(p._conteo)
      }

      const isVector = capaBase.value === 'vectorial'
      const strokeColor = isVector ? '#ffffff' : (capaBase.value === 'oscuro' ? '#38bdf8' : (capaBase.value === 'satelite' ? '#ffffff' : '#ffffff'))

      if (esDepartamento.value) {
        return {
          fillColor,
          fillOpacity: isVector ? 0.85 : (capaBase.value === 'satelite' ? 0.22 : (capaBase.value === 'oscuro' ? 0.35 : 0.28)),
          color: strokeColor,
          weight: isVector ? 2 : 1.8,
          opacity: 1,
        }
      } else {
        return {
          fillColor: isMuniActive ? '#2563eb' : (isVector ? '#e2e8f0' : '#334155'),
          fillOpacity: isMuniActive ? 0.04 : (isVector ? 0.12 : 0.22),
          color: isMuniActive ? '#1d4ed8' : (isVector ? '#cbd5e1' : '#94a3b8'),
          weight: isMuniActive ? 3.5 : 1,
          dashArray: isMuniActive ? null : '3, 4',
        }
      }
    },
    onEachFeature(feat, layer) {
      const p = feat.properties

      // ── Hover Peek: Tarjeta enriquecida SOLO a nivel departamento ──
      if (esDepartamento.value) {
        const pctBarra = Math.min(100, Math.round(((p._conteo || 0) / 400) * 100))
        const tooltipHtml = `
          <div class="p-3 min-w-[190px] text-gray-800">
            <div class="flex items-center justify-between gap-2 mb-1.5">
              <span class="text-[10px] font-bold px-1.5 py-0.2 rounded-full text-white" style="background:${p._subregion?.color || '#2563eb'}">
                ${p._subregion?.nombre || 'Caldas'}
              </span>
              <span class="text-[10px] text-gray-400 font-medium">Municipio</span>
            </div>

            <div class="flex items-center gap-1.5 mb-1">
              <span class="w-2.5 h-2.5 rounded-full inline-block shadow-xs" style="background:${p._colorMuni}"></span>
              <strong class="text-sm font-black text-gray-900 leading-tight">${p._nombre}</strong>
            </div>

            <p class="text-xs text-blue-700 font-black mb-1">👥 ${(p._conteo || 0).toLocaleString('es-CO')} simpatizantes</p>

            <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2 overflow-hidden">
              <div class="bg-blue-600 h-full rounded-full" style="width: ${Math.max(pctBarra, 4)}%"></div>
            </div>

            <div class="pt-1.5 border-t border-gray-100 text-[10px] text-gray-500 space-y-0.5">
              <p>📋 Censo estimado: <strong>${(p._oportunidad?.censo || 10000).toLocaleString('es-CO')}</strong></p>
              <p>⭐ Categoría: <strong>${p._oportunidad?.categoria || 'Normal'}</strong></p>
              <p class="text-blue-600 font-semibold pt-0.5">Clic para hacer zoom y ver puestos →</p>
            </div>
          </div>
        `

        layer.bindTooltip(tooltipHtml, {
          sticky: true,
          opacity: 0.98,
          className: 'custom-leaflet-tooltip',
        })
      }

      layer.on({
        mouseover(e) {
          if (esDepartamento.value) {
            e.target.setStyle({ weight: 3, fillOpacity: 0.55, color: '#ffffff' })
            e.target.bringToFront()
          }
        },
        mouseout(e) {
          if (esDepartamento.value) {
            geojsonLayer?.resetStyle(e.target)
          }
        },
        click(e) {
          if (esDepartamento.value) {
            L.DomEvent.stopPropagation(e)
            detenerAutoTour()
            map.fitBounds(e.target.getBounds(), { padding: [40, 40], maxZoom: 14, animate: true, duration: 1 })
            if (p._id) emit('click-territorio', p._id)
          }
        },
      })
    },
  }).addTo(map)

  if (esDepartamento.value) {
    try {
      map.fitBounds(geojsonLayer.getBounds(), { padding: [15, 15] })
    } catch {}
  }
}

/* ── Formateador de Nombres Claros para Pines de Puestos ──── */
function obtenerNombreCortoPuesto(nombre) {
  if (!nombre) return 'Puesto'
  let s = String(nombre).trim()
  s = s.toLowerCase().replace(/(?:^|\s|\/|-)\S/g, c => c.toUpperCase())
  s = s
    .replace(/^Institucion Educativa\s+/i, 'I.E. ')
    .replace(/^Inst\.? Educativa\s+/i, 'I.E. ')
    .replace(/^Institución Educativa\s+/i, 'I.E. ')
    .replace(/^Colegio\s+/i, 'Col. ')
    .replace(/^Escuela\s+/i, 'Esc. ')
    .replace(/^Concentracion\s+/i, 'Conc. ')
    .replace(/^Concentración\s+/i, 'Conc. ')
    .replace(/^Corregimiento\s+/i, 'Cgto. ')
    .replace(/^Vereda\s+/i, 'Vda. ')
    .replace(/^Puesto\s+/i, '')
    .trim()
  return s
}

/* ── Render de Puestos de Votación con Pines Inteligentes ──── */
function renderPuestos() {
  clusterLayer?.remove()
  clusterLayer = null
  puestosVisiblesCount.value = 0

  let puestosFiltrados = props.puestos.filter(p => p.latitud && p.longitud && !isNaN(+p.latitud) && !isNaN(+p.longitud))

  if (comunaFiltro.value) {
    puestosFiltrados = puestosFiltrados.filter(p => 
      p.comuna === comunaFiltro.value || 
      p.territorio_id === comunaFiltro.value || 
      p.zona === comunaFiltro.value ||
      limpiarNombreComuna(p.comuna) === limpiarNombreComuna(comunaFiltro.value) ||
      (zonaActivaInfo.value && zonaActivaInfo.value.puestos?.some(zp => zp.id === p.id))
    )
  }

  puestosVisiblesCount.value = puestosFiltrados.length
  if (puestosFiltrados.length === 0) return

  clusterLayer = L.markerClusterGroup({
    showCoverageOnHover: false,
    maxClusterRadius: 32,
    spiderfyOnMaxZoom: true,
    iconCreateFunction(cluster) {
      const markers = cluster.getAllChildMarkers()
      let totalSimp = 0
      markers.forEach(m => {
        totalSimp += m.options._totalSimpatizantes || 0
      })

      let ringClass = 'border-white/90 ring-1 ring-black/10'
      if (modoPines.value === 'semaforo') {
        const totalMeta = markers.length * 30
        const pct = (totalSimp / totalMeta) * 100
        if (pct >= 75) ringClass = 'border-emerald-400 ring-2 ring-emerald-500/40'
        else if (pct >= 35) ringClass = 'border-amber-400 ring-2 ring-amber-500/40'
        else ringClass = 'border-rose-400 ring-2 ring-rose-500/30'
      }

      return L.divIcon({
        html: `
          <div class="flex items-center gap-1.5 bg-slate-900/95 backdrop-blur-md text-white font-bold text-[11px] px-2.5 py-1 rounded-full shadow-xl border-2 ${ringClass} transform -translate-x-1/2 -translate-y-1/2 whitespace-nowrap hover:scale-105 transition-all">
            <span class="text-xs">🗳️</span>
            <span>${cluster.getChildCount()} puestos</span>
            ${totalSimp > 0 ? `<span class="bg-blue-600 text-white text-[10px] font-black px-1.5 py-0.2 rounded-full ml-0.5">${totalSimp} 👥</span>` : ''}
          </div>
        `,
        className: 'custom-cluster-icon',
        iconSize: [0, 0],
        iconAnchor: [0, 0],
      })
    },
  })

  puestosFiltrados.forEach(p => {
    const totalSimp = p.conteo ?? p.simpatizantes_count ?? 0
    const meta = Math.max(20, (p.total_mesas || 1) * 25)
    const pctMeta = Math.min(100, Math.round((totalSimp / meta) * 100))
    const nombreCorto = obtenerNombreCortoPuesto(p.nombre)

    // Color del Pin (Estándar vs Semáforo)
    let pinColor = '#2563eb' // Azul electoral
    let dotColor = '#3b82f6'
    let badgeColor = 'bg-blue-600'
    let semaforoBadge = ''

    if (modoPines.value === 'semaforo') {
      if (pctMeta >= 75) {
        pinColor = '#10b981' // Esmeralda
        dotColor = '#10b981'
        badgeColor = 'bg-emerald-600'
        semaforoBadge = '<span class="text-[9px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.2 rounded-full">✓ Meta 75%+</span>'
      } else if (pctMeta >= 35) {
        pinColor = '#f59e0b' // Ámbar
        dotColor = '#f59e0b'
        badgeColor = 'bg-amber-600'
        semaforoBadge = '<span class="text-[9px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.2 rounded-full">⚠️ En progreso</span>'
      } else {
        pinColor = '#e11d48' // Rosa/Rojo
        dotColor = '#e11d48'
        badgeColor = 'bg-rose-600'
        semaforoBadge = '<span class="text-[9px] bg-rose-100 text-rose-800 font-bold px-1.5 py-0.2 rounded-full">🚨 Crítico</span>'
      }
    }

    const pinHtml = `
      <div class="puesto-smart-pin group cursor-pointer -translate-x-1/2 -translate-y-full hover:scale-110 hover:z-[999] transition-all duration-150">
        <div class="relative flex flex-col items-center">
          <!-- Pastilla Informativa Superior (Nombre Escuela + Cantidad) -->
          <div class="bg-white/95 backdrop-blur-md text-gray-900 font-bold text-[10px] px-2 py-0.5 rounded-full shadow-md border border-gray-200/90 flex items-center gap-1.5 whitespace-nowrap mb-0.5 group-hover:border-blue-500 group-hover:shadow-xl transition-all">
            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: ${dotColor}"></span>
            <span class="max-w-[130px] truncate leading-tight">${nombreCorto}</span>
            <span class="${badgeColor} text-white font-black text-[9px] px-1.5 py-0.2 rounded-full leading-none flex-shrink-0">
              ${totalSimp}
            </span>
          </div>

          <!-- Pin con Punta Geográfica e Icono de Urna -->
          <div class="relative flex flex-col items-center -mt-0.5">
            <svg width="24" height="28" viewBox="0 0 24 28" fill="none" class="filter drop-shadow-md">
              <path d="M12 0C5.373 0 0 5.373 0 12C0 20.5 12 28 12 28C12 28 24 20.5 24 12C24 5.373 18.627 0 12 0Z" fill="${pinColor}"/>
              <circle cx="12" cy="11" r="6.5" fill="#FFFFFF"/>
              <!-- Icono Urna Electoral -->
              <path d="M8.5 9H15.5V14H8.5V9Z" fill="${pinColor}" fill-opacity="0.25"/>
              <path d="M9 8.5V7.5H15V8.5M8.5 10H15.5M10.5 12H13.5" stroke="${pinColor}" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
      </div>
    `

    const icon = L.divIcon({
      html: pinHtml,
      className: 'custom-pin-icon',
      iconSize: [0, 0],
      iconAnchor: [0, 0],
      popupAnchor: [0, -36],
    })

    const marker = L.marker([+p.latitud, +p.longitud], {
      icon,
      _totalSimpatizantes: totalSimp,
    })

    const popupHtml = `
      <div class="p-3 min-w-[220px] font-sans">
        <div class="flex items-center justify-between gap-1 mb-1">
          <span class="text-[10px] font-bold text-blue-600 uppercase">Puesto de Votación</span>
          ${semaforoBadge}
        </div>
        <h4 class="font-extrabold text-sm text-gray-900 leading-tight mb-1">${p.nombre}</h4>
        <p class="text-xs text-gray-500 mb-2 truncate">${p.direccion || 'Sin dirección registrada'}</p>

        <div class="grid grid-cols-3 gap-1 bg-gray-50 p-1.5 rounded-lg text-center text-xs mb-2 border border-gray-100">
          <div>
            <p class="font-bold text-blue-700">${totalSimp}</p>
            <p class="text-[9px] text-gray-400 font-medium">Simpat.</p>
          </div>
          <div>
            <p class="font-bold text-gray-800">${p.total_mesas ?? 0}</p>
            <p class="text-[9px] text-gray-400 font-medium">Mesas</p>
          </div>
          <div>
            <p class="font-bold text-gray-800">${(p.censo || 0).toLocaleString('es-CO')}</p>
            <p class="text-[9px] text-gray-400 font-medium">Censo</p>
          </div>
        </div>

        <button
          onclick="window.dispatchEvent(new CustomEvent('abrir-puesto-mapa', { detail: ${p.id} }))"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-1.5 px-3 rounded-lg shadow-xs transition"
        >
          Ver Ficha Técnica 360° →
        </button>
      </div>
    `
    marker.bindPopup(popupHtml, { maxWidth: 280 })
    marker.on('click', () => {
      emit('click-puesto', p)
    })

    clusterLayer.addLayer(marker)
  })

  map.addLayer(clusterLayer)
}

function zoomMunicipio(municipio) {
  if (!map) return

  // 1. Intentar hacer zoom por puestos si ya están cargados
  if (props.puestos && props.puestos.length > 0) {
    const pts = props.puestos.filter(p => p.latitud && p.longitud).map(p => [+p.latitud, +p.longitud])
    if (pts.length > 0) {
      const bounds = L.latLngBounds(pts)
      if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15, animate: true, duration: 1 })
        return
      }
    }
  }

  // 2. Intentar hacer zoom por el GeoJSON del municipio
  if (caldas && caldas.features) {
    const nomBuscado = norm(municipio.nombre)
    const feat = caldas.features.find(f => 
      norm(f.properties.nombre) === nomBuscado || 
      norm(f.properties.nombre_upper) === nomBuscado ||
      f.properties.codigo_divipola === municipio.codigo_divipola
    )
    if (feat) {
      const tempLayer = L.geoJSON(feat)
      const bounds = tempLayer.getBounds()
      if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14, animate: true, duration: 1 })
        return
      }
    }
  }

  // 3. Fallback por centroide si está disponible
  if (municipio.centroide_lat && municipio.centroide_lng) {
    map.flyTo([+municipio.centroide_lat, +municipio.centroide_lng], 13, { duration: 1 })
  }
}

/* ── Watchers ──────────────────────────────────────────────── */
watch(() => props.hijos, () => {
  renderMunicipios()
}, { deep: false })

watch(() => props.puestos, (nuevosPuestos) => {
  renderPuestos()
  if (!esDepartamento.value && nuevosPuestos && nuevosPuestos.length > 0 && !comunaFiltro.value) {
    const pts = nuevosPuestos.filter(p => p.latitud && p.longitud).map(p => [+p.latitud, +p.longitud])
    if (pts.length > 0) {
      const bounds = L.latLngBounds(pts)
      if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15, animate: true, duration: 1 })
      }
    }
  }
}, { deep: false })

watch(() => props.territorioActual, t => {
  if (!t || !map) return
  comunaFiltro.value = null
  mostrarTodosPuestos.value = true
  renderMunicipios()

  if (t.tipo === 'municipio') {
    zoomMunicipio(t)
    renderPuestos()
  } else if (t.tipo === 'departamento') {
    clusterLayer?.remove()
    puestosVisiblesCount.value = 0
    try {
      if (geojsonLayer) map.fitBounds(geojsonLayer.getBounds(), { padding: [15, 15], animate: true })
    } catch {}
  }
})

watch(() => props.panelOpen, () => {
  setTimeout(() => {
    map?.invalidateSize()
  }, 250)
})

defineExpose({
  flyToCoords: (lat, lng, zoom = 15) => {
    map?.flyTo([lat, lng], zoom, { duration: 1.5 })
  }
})
</script>

<style>
.custom-pin-icon, .custom-cluster-icon, .marker-cluster {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
}
.marker-cluster div {
  background: transparent !important;
  border: none !important;
}
.custom-leaflet-tooltip {
  padding: 0 !important;
  border-radius: 14px !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
  border: 1px solid #e5e7eb !important;
  background: white !important;
  overflow: hidden !important;
}
.leaflet-popup-content-wrapper {
  border-radius: 16px !important;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2) !important;
  padding: 0 !important;
  overflow: hidden !important;
}
.leaflet-popup-content {
  margin: 0 !important;
  line-height: normal !important;
}
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.dark-map .leaflet-tile-pane {
  filter: brightness(0.95);
}
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.25s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.97);
}
@keyframes bounce-short {
  0%, 100% { transform: translate(-50%, 0); }
  50% { transform: translate(-50%, -4px); }
}
.animate-bounce-short {
  animation: bounce-short 2s infinite ease-in-out;
}
</style>
