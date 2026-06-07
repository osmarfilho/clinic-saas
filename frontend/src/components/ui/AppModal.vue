<script setup lang="ts">
import { X } from 'lucide-vue-next'

defineProps<{
  open: boolean
  title: string
}>()

defineEmits<{
  close: []
}>()
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-3 sm:p-4">
      <section class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-xl sm:max-h-[calc(100dvh-2rem)]">
        <header class="flex items-center justify-between gap-3 border-b border-[#E2E8F0] px-4 py-3 sm:px-5 sm:py-4">
          <h2 class="min-w-0 truncate text-base font-semibold text-[#0F172A] sm:text-lg">{{ title }}</h2>
          <button
            type="button"
            class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"
            aria-label="Fechar"
            @click="$emit('close')"
          >
            <X class="h-4 w-4" />
          </button>
        </header>
        <div class="overflow-y-auto p-4 sm:p-5">
          <slot />
        </div>
      </section>
    </div>
  </Teleport>
</template>
