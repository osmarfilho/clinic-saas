<script setup lang="ts">
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
    <div v-if="open" class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4">
      <section class="w-full max-w-3xl rounded-lg bg-white shadow-xl">
        <header class="flex items-center justify-between border-b border-[#E2E8F0] px-5 py-4">
          <h2 class="text-lg font-semibold text-[#0F172A]">{{ title }}</h2>
          <button
            type="button"
            class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"
            aria-label="Fechar"
            @click="$emit('close')"
          >
            x
          </button>
        </header>
        <div class="p-5">
          <slot />
        </div>
      </section>
    </div>
  </Teleport>
</template>
