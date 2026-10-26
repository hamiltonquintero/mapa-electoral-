<template>
  <div class="p-6 max-w-7xl mx-auto space-y-5">
    <!-- Cabecera -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Simpatizantes</h2>
        <p v-if="pagination" class="text-sm text-gray-500">
          {{ pagination.total.toLocaleString('es-CO') }} registros
        </p>
      </div>
      <div class="flex items-center gap-2">
        <RouterLink
          v-if="auth.hasPermission('simpatizante.crear')"
          to="/simpatizantes/nuevo"
          class="btn-primary btn-sm"
        >
          <PlusIcon class="w-4 h-4" />
          Nuevo
        </RouterLink>
      </div>
    </div>

    <!-- Filtros de búsqueda -->
    <div class="card p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
          <label class="label text-xs">Buscar</label>
          <input
            v-model="filtros.buscar"
            type="search"
            class="input text-sm"
            placeholder="Nombre, apellido o cédula…"
            @keyup.enter="cargar(1)"
          />
        </div>
        <div>
          <label class="label text-xs">Estado</label>
          <select v-model="filtros.estado" class="input text-sm" @change="cargar(1)">
            <option value="">Todos</option>
            <option value="validado">Validado</option>
            <option value="pendiente_evidencia">Pendiente evidencia</option>
            <option value="borrador">Borrador</option>
            <option value="rechazado">Rechazado</option>
          </select>
        </div>
        <div>
          <label class="label text-xs">Territorio</label>
          <input v-model="filtros.territorio_id" type="text" class="input text-sm" placeholder="ID territorio…" @change="cargar(1)" />
        </div>
        <div class="flex items-end gap-2">
          <button class="btn-primary btn-sm flex-1" @click="cargar(1)">Buscar</button>
          <button class="btn-secondary btn-sm" @click="resetFiltros">Limpiar</button>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card overflow-hidden">
      <div v-if="loading" class="flex justify-center py-16">
        <LoadingSpinner label="Cargando simpatizantes…" />
      </div>

      <div v-else-if="!simpatizantes.length" class="flex flex-col items-center py-16 text-gray-400">
        <UsersIcon class="w-10 h-10 mb-3" />
        <p class="text-sm">No se encontraron registros</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th class="text-left px-4 py-3 font-medium text-gray-600">Nombre</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Municipio</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Lote</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">Estado</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Fecha</th>
              <th class="px-4 py-3" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="s in simpatizantes"
              :key="s.id"
              class="hover:bg-gray-50 transition cursor-pointer"
              @click="$router.push(`/simpatizantes/${s.id}`)"
            >
              <td class="px-4 py-3">
                <p class="font-medium text-gray-900">{{ s.nombres }} {{ s.apellidos }}</p>
              </td>
              <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ s.territorio?.nombre ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-500 hidden lg:table-cell">{{ s.lote?.descripcion ?? '—' }}</td>
              <td class="px-4 py-3">
                <EstadoBadge :estado="s.estado" />
              </td>
              <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">
                {{ formatDate(s.creado_en) }}
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  class="btn-ghost btn-sm p-1"
                  @click.stop="$router.push(`/simpatizantes/${s.id}`)"
                >
                  <ChevronRightIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="pagination && pagination.last_page > 1" class="px-4 py-3 border-t border-gray-100">
        <Pagination :pagination="pagination" @page="cargar" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { PlusIcon, UsersIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import { useAuthStore }           from '@/stores/auth'
import { simpatizanteService }    from '@/services/simpatizanteService'
import { useToastStore }          from '@/stores/toast'
import LoadingSpinner             from '@/components/ui/LoadingSpinner.vue'
import Pagination                 from '@/components/ui/Pagination.vue'
import EstadoBadge                from '@/components/simpatizantes/EstadoBadge.vue'
import dayjs from 'dayjs'

const auth   = useAuthStore()
const toasts = useToastStore()

const simpatizantes = ref([])
const pagination    = ref(null)
const loading       = ref(false)

const filtros = reactive({
  buscar:       '',
  estado:       '',
  territorio_id: '',
})

function formatDate(d) {
  return d ? dayjs(d).format('DD/MM/YYYY') : '—'
}

async function cargar(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 25 }
    if (filtros.buscar)        params.buscar        = filtros.buscar
    if (filtros.estado)        params.estado        = filtros.estado
    if (filtros.territorio_id) params.territorio_id = filtros.territorio_id

    const { data } = await simpatizanteService.list(params)
    simpatizantes.value = data.data ?? []
    pagination.value    = data.pagination ?? data.meta ?? null
  } catch (e) {
    toasts.error('Error al cargar simpatizantes')
  } finally {
    loading.value = false
  }
}

function resetFiltros() {
  filtros.buscar        = ''
  filtros.estado        = ''
  filtros.territorio_id = ''
  cargar(1)
}

onMounted(() => cargar())
</script>
