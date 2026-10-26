<template>
  <RouterLink
    :to="item.to"
    class="flex items-center gap-3 rounded-md px-2 py-2 text-sm font-medium transition-colors"
    :class="isActive
      ? 'bg-primary-600 text-white'
      : 'text-gray-400 hover:bg-gray-800 hover:text-white'"
    :title="collapsed ? item.label : undefined"
  >
    <component :is="item.icon" class="flex-shrink-0 w-5 h-5" />
    <Transition name="fade">
      <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
    </Transition>
    <Transition name="fade">
      <span
        v-if="!collapsed && item.badge"
        class="ml-auto inline-flex items-center rounded-full bg-primary-500 px-1.5 py-0.5 text-xs font-medium text-white"
      >{{ item.badge }}</span>
    </Transition>
  </RouterLink>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const props = defineProps({
  item:      { type: Object, required: true },
  collapsed: { type: Boolean, default: false },
})

const route    = useRoute()
const isActive = computed(() => route.path.startsWith(props.item.to))
</script>
