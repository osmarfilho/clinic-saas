<script setup lang="ts">
defineProps<{
  message: string
  type?: 'success' | 'error'
}>()
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="translate-y-2 opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-2 opacity-0"
  >
    <div
      v-if="message"
      class="fixed right-4 top-20 z-50 max-w-sm rounded-2xl border bg-white px-4 py-3 text-sm font-medium shadow-md"
      :class="type === 'error' ? 'border-rose-200 text-rose-700' : 'border-emerald-200 text-emerald-700'"
      role="status"
      aria-live="polite"
    >
      {{ message }}
    </div>
  </Transition>
</template>
