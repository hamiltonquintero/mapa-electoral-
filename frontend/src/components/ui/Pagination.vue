<template>
  <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between text-sm">
    <p class="text-gray-500">
      Mostrando {{ pagination.from }}–{{ pagination.to }} de {{ pagination.total }}
    </p>
    <div class="flex items-center gap-1">
      <button
        class="btn-ghost btn-sm px-2"
        :disabled="pagination.current_page <= 1"
        @click="$emit('page', pagination.current_page - 1)"
      >
        <ChevronLeftIcon class="w-4 h-4" />
      </button>

      <template v-for="p in pages" :key="p">
        <span v-if="p === '...'" class="px-2 text-gray-400">…</span>
        <button
          v-else
          class="btn-sm w-8 h-8 rounded"
          :class="p === pagination.current_page
            ? 'bg-primary-600 text-white font-semibold'
            : 'btn-ghost'"
          @click="$emit('page', p)"
        >{{ p }}</button>
      </template>

      <button
        class="btn-ghost btn-sm px-2"
        :disabled="pagination.current_page >= pagination.last_page"
        @click="$emit('page', pagination.current_page + 1)"
      >
        <ChevronRightIcon class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  pagination: { type: Object, default: null },
})
defineEmits(['page'])

const pages = computed(() => {
  if (!props.pagination) return []
  const { current_page: cur, last_page: last } = props.pagination
  const result = []

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= cur - 1 && i <= cur + 1)) {
      result.push(i)
    } else if (result[result.length - 1] !== '...') {
      result.push('...')
    }
  }

  return result
})
</script>
