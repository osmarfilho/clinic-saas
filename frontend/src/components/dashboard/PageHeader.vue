<script setup lang="ts">
defineProps<{
  title: string
  subtitle: string
}>()
</script>

<template>
  <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
      <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{{ title }}</h1>
      <p class="mt-1 text-sm text-muted">{{ subtitle }}</p>
    </div>

    <span class="inline-flex items-center gap-2 rounded-full bg-brand-soft px-3 py-2 text-xs font-semibold text-brand-strong sm:px-4 sm:text-sm">
      <span class="h-2 w-2 rounded-full bg-brand" />
      Clínica em operação
    </span>
  </header>
</template>
