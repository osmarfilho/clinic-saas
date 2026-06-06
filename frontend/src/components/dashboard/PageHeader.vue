<script setup lang="ts">
defineProps<{
  title: string
  subtitle: string
}>()
</script>

<template>
  <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ title }}</h1>
      <p class="mt-1 text-sm text-muted">{{ subtitle }}</p>
    </div>

    <span class="inline-flex items-center gap-2 rounded-full bg-brand-soft px-4 py-2 text-sm font-semibold text-brand-strong">
      <span class="h-2 w-2 rounded-full bg-brand" />
      Clínica em operação
    </span>
  </header>
</template>
