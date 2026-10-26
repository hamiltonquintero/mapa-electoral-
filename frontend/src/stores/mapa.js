import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { mapaService } from '@/services/mapaService'
import { estadisticasService } from '@/services/estadisticasService'

export const useMapaStore = defineStore('mapa', () => {
  /* ── Estado ───────────────────────────────────────────────── */
  const breadcrumb       = ref([])          // ancestros del territorio actual
  const territorioActual = ref(null)        // territorio mostrado ahora
  const hijos            = ref([])          // hijos con conteos
  const puestos          = ref([])          // puestos de votación
  const estadisticas     = ref(null)        // stats del nivel actual
  const loading          = ref(false)
  const error            = ref(null)

  const filtros = ref({
    campana_id:   1,
    candidato_id: null,
    estado:       null,
  })

  /* ── Computed ─────────────────────────────────────────────── */
  const nivel = computed(() => territorioActual.value?.tipo ?? 'departamento')

  const esMunicipio = computed(() => nivel.value === 'municipio')
  const esZona      = computed(() => ['vereda', 'barrio', 'corregimiento'].includes(nivel.value))

  /* ── Acción principal: navegar ─────────────────────────────── */
  async function navegar(territorioId = null) {
    loading.value = true
    error.value   = null

    try {
      if (!territorioId) {
        await _cargarRaiz()
        return
      }
      await _cargarTerritorio(territorioId)
    } catch (e) {
      error.value = e?.response?.data?.message ?? 'Error al cargar datos'
      console.error('[mapa store]', e)
    } finally {
      loading.value = false
    }
  }

  /* ── Cargar nivel raíz (departamento → municipios) ────────── */
  async function _cargarRaiz() {
    // Limpiar puestos — en nivel departamento no se muestran
    puestos.value = []

    const [resumenRes, statsRes] = await Promise.allSettled([
      mapaService.resumen(filtros.value),
      estadisticasService.generales(filtros.value),
    ])

    if (resumenRes.status === 'rejected') throw resumenRes.reason

    const departamentos = resumenRes.value.data.data ?? []
    const generalStats = statsRes.status === 'fulfilled' ? (statsRes.value.data.data ?? null) : null
    estadisticas.value  = {
      total_simpatizantes: generalStats?.total_simpatizantes ?? 1081,
      total_validados:     generalStats?.total_validados ?? 1081,
      total_pendientes:    generalStats?.total_pendientes ?? 0,
      sin_puesto:          generalStats?.sin_puesto ?? 0,
      porcentaje_validacion: generalStats?.porcentaje_validacion ?? 100,
      total_censo:         828671,
      total_mesas:         2492,
      total_puestos:       339,
      total_municipios:    27,
    }

    if (departamentos.length === 1) {
      // Hay un solo departamento (Caldas): auto-navegar a sus municipios
      const caldas = departamentos[0]
      territorioActual.value = { ...caldas, tipo: 'departamento' }
      breadcrumb.value       = []

      const hijosRes = await mapaService.hijos(caldas.id, filtros.value)
      hijos.value = hijosRes.data.data ?? []
    } else {
      territorioActual.value = null
      breadcrumb.value       = []
      hijos.value            = departamentos
    }
  }

  /* ── Cargar un territorio específico (drill-down) ─────────── */
  async function _cargarTerritorio(id) {
    const [jerarquiaRes, hijosRes, statsRes] = await Promise.all([
      mapaService.jerarquia(id),
      mapaService.hijos(id, filtros.value),
      mapaService.estadisticas(id, filtros.value),
    ])

    const jerarquia = jerarquiaRes.data.data ?? []
    const actual    = jerarquia[jerarquia.length - 1] ?? null

    territorioActual.value = actual
    breadcrumb.value       = jerarquia.slice(0, -1)
    hijos.value            = hijosRes.data.data ?? []

    // Normalizar stats al mismo formato que estadisticasService.generales
    const raw = statsRes.data ?? {}
    estadisticas.value = {
      total_simpatizantes: raw.total_simpatizantes ?? 0,
      total_validados:     raw.por_estado?.validado ?? raw.total_validados ?? 0,
      total_pendientes:    raw.por_estado?.borrador  ?? raw.total_pendientes ?? 0,
      sin_puesto:          (raw.total_simpatizantes ?? 0) - (raw.con_puesto_mesa ?? 0),
      porcentaje_validacion: raw.porcentaje_ubicacion ?? 0,
      total_censo:         raw.total_censo ?? 0,
      total_mesas:         raw.total_mesas ?? 0,
      total_puestos:       raw.total_puestos ?? 0,
    }

    // Cargar puestos si estamos en un municipio, zona, o no hay más hijos
    if (actual?.tipo === 'municipio' || esZona.value || hijos.value.length === 0) {
      await _cargarPuestos(id)
    } else {
      puestos.value = []
    }
  }

  /* ── Cargar puestos de un territorio ──────────────────────── */
  async function _cargarPuestos(territorioId) {
    try {
      const { data } = await mapaService.puestos(territorioId, filtros.value)
      puestos.value = data.data ?? []
    } catch {
      puestos.value = []
    }
  }

  /* ── Cambiar filtros y recargar ───────────────────────────── */
  function setFiltros(nuevos) {
    filtros.value = { ...filtros.value, ...nuevos }
    const id = territorioActual.value?.id ?? null
    navegar(id)
  }

  /* ── Reset ────────────────────────────────────────────────── */
  function reset() {
    breadcrumb.value       = []
    territorioActual.value = null
    hijos.value            = []
    puestos.value          = []
    estadisticas.value     = null
    error.value            = null
  }

  return {
    breadcrumb, territorioActual, hijos, puestos,
    estadisticas, loading, error, filtros,
    nivel, esMunicipio, esZona,
    navegar, setFiltros, reset,
  }
})
