<template>
  <div class="p-6 max-w-4xl mx-auto space-y-5">
    <!-- Cabecera -->
    <div class="flex items-center gap-3">
      <button class="btn-ghost btn-sm p-1" @click="$router.back()">
        <ArrowLeftIcon class="w-5 h-5" />
      </button>
      <div class="flex-1 min-w-0">
        <h2 class="text-lg font-semibold text-gray-900 truncate">
          {{ simpatizante?.nombres }} {{ simpatizante?.apellidos }}
        </h2>
        <p class="text-sm text-gray-500">Simpatizante</p>
      </div>
      <EstadoBadge v-if="simpatizante" :estado="simpatizante.estado" />
      <div v-if="simpatizante && auth.hasPermission('simpatizante.editar')" class="flex gap-2">
        <RouterLink :to="`/simpatizantes/${id}/editar`" class="btn-secondary btn-sm">
          <PencilIcon class="w-4 h-4" /> Editar
        </RouterLink>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <LoadingSpinner label="Cargando…" />
    </div>

    <template v-else-if="simpatizante">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Columna principal -->
        <div class="lg:col-span-2 space-y-5">
          <!-- Datos personales -->
          <div class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Datos personales</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <DetalleField label="Nombres" :value="simpatizante.nombres" />
              <DetalleField label="Apellidos" :value="simpatizante.apellidos" />
              <DetalleField label="Cédula" :value="simpatizante.cedula ? '***' + simpatizante.cedula.slice(-4) : '—'" />
              <DetalleField label="Teléfono" :value="simpatizante.telefono ?? '—'" />
              <DetalleField label="Correo" :value="simpatizante.correo ?? '—'" />
              <DetalleField label="Dirección" :value="simpatizante.direccion ?? '—'" />
            </dl>
          </div>

          <!-- Ubicación electoral -->
          <div class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Ubicación electoral</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <DetalleField label="Municipio/Zona" :value="simpatizante.territorio?.nombre ?? '—'" />
              <DetalleField label="Puesto de votación" :value="simpatizante.puesto_votacion?.nombre ?? '—'" />
              <DetalleField label="Mesa" :value="simpatizante.mesa ?? '—'" />
              <DetalleField label="Líder responsable" :value="simpatizante.lider?.name ?? '—'" />
            </dl>
          </div>

          <!-- Candidatos que apoya -->
          <div v-if="simpatizante.candidatos?.length" class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Candidatos que apoya</h3>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="c in simpatizante.candidatos"
                :key="c.id"
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium text-white"
                :style="{ backgroundColor: c.color ?? '#2563eb' }"
              >
                {{ c.nombre }} · {{ c.cargo }}
              </span>
            </div>
          </div>

          <!-- Historial de cambios -->
          <div v-if="simpatizante.historial_cambios?.length" class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Historial de cambios</h3>
            <ol class="relative border-l border-gray-200 space-y-4 pl-4">
              <li
                v-for="h in simpatizante.historial_cambios"
                :key="h.id"
                class="text-xs text-gray-600"
              >
                <span class="absolute -left-1.5 w-3 h-3 rounded-full bg-primary-400 ring-2 ring-white" />
                <time class="text-gray-400">{{ formatDate(h.creado_en) }}</time>
                <span class="mx-1">·</span>
                <strong>{{ h.usuario?.name }}</strong> cambió
                <em>{{ h.campo }}</em>
              </li>
            </ol>
          </div>
        </div>

        <!-- Columna lateral -->
        <div class="space-y-5">
          <!-- Formulario físico -->
          <div class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Formulario físico</h3>
            <div v-if="simpatizante.formulario">
              <div class="aspect-[3/4] bg-gray-100 rounded-lg overflow-hidden mb-2">
                <img
                  v-if="simpatizante.formulario.url_temporal"
                  :src="simpatizante.formulario.url_temporal"
                  class="w-full h-full object-cover"
                  alt="Formulario"
                />
                <div v-else class="flex items-center justify-center h-full text-gray-400">
                  <DocumentIcon class="w-12 h-12" />
                </div>
              </div>
              <p class="text-xs text-gray-500">
                Firmado el {{ formatDate(simpatizante.formulario.fecha_firma) }}
              </p>
            </div>
            <div v-else class="flex flex-col items-center py-6 text-gray-400">
              <DocumentIcon class="w-8 h-8 mb-2" />
              <p class="text-xs">Sin formulario adjunto</p>
            </div>
          </div>

          <!-- Metadatos -->
          <div class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Metadatos</h3>
            <dl class="space-y-2">
              <DetalleField label="Lote" :value="simpatizante.lote?.descripcion ?? '—'" small />
              <DetalleField label="Creado por" :value="simpatizante.creado_por_usuario?.name ?? '—'" small />
              <DetalleField label="Fecha registro" :value="formatDate(simpatizante.creado_en)" small />
            </dl>
          </div>

          <!-- Acciones de estado -->
          <div v-if="auth.hasPermission('simpatizante.editar')" class="card p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Cambiar estado</h3>
            <div class="space-y-2">
              <button
                v-for="accion in accionesEstado"
                :key="accion.estado"
                class="btn-secondary btn-sm w-full justify-center"
                :disabled="simpatizante.estado === accion.estado"
                @click="cambiarEstado(accion.estado)"
              >
                {{ accion.label }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  ArrowLeftIcon, PencilIcon, DocumentIcon,
} from '@heroicons/vue/24/outline'
import { simpatizanteService } from '@/services/simpatizanteService'
import { useAuthStore }        from '@/stores/auth'
import { useToastStore }       from '@/stores/toast'
import EstadoBadge             from '@/components/simpatizantes/EstadoBadge.vue'
import DetalleField            from '@/components/simpatizantes/DetalleField.vue'
import LoadingSpinner          from '@/components/ui/LoadingSpinner.vue'
import dayjs from 'dayjs'

const route  = useRoute()
const auth   = useAuthStore()
const toasts = useToastStore()
const id     = route.params.id

const simpatizante = ref(null)
const loading      = ref(false)

const accionesEstado = [
  { estado: 'validado',            label: '✓ Marcar como validado' },
  { estado: 'pendiente_evidencia', label: '⏳ Marcar pendiente evidencia' },
  { estado: 'rechazado',           label: '✗ Rechazar' },
]

function formatDate(d) {
  return d ? dayjs(d).format('DD/MM/YYYY') : '—'
}

async function cargar() {
  loading.value = true
  try {
    const { data } = await simpatizanteService.get(id)
    simpatizante.value = data.data ?? data
  } catch {
    toasts.error('No se pudo cargar el simpatizante')
  } finally {
    loading.value = false
  }
}

async function cambiarEstado(estado) {
  try {
    await simpatizanteService.cambiarEstado(id, estado)
    toasts.success('Estado actualizado')
    await cargar()
  } catch {
    toasts.error('Error al cambiar estado')
  }
}

onMounted(cargar)
</script>
