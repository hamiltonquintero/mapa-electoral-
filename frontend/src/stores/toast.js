import { defineStore } from 'pinia'
import { ref } from 'vue'

let _nextId = 1

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])

  function add(message, type = 'info', duration = 4000) {
    const id = _nextId++
    toasts.value.push({ id, message, type })

    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }

    return id
  }

  function remove(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  const success = (msg, dur) => add(msg, 'success', dur)
  const error   = (msg, dur) => add(msg, 'error',   dur ?? 6000)
  const warning = (msg, dur) => add(msg, 'warning', dur)
  const info    = (msg, dur) => add(msg, 'info',    dur)

  return { toasts, add, remove, success, error, warning, info }
})
