<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next'

type Accent = 'emerald' | 'teal' | 'sky' | 'amber' | 'violet' | 'rose'

defineProps<{
  icon: LucideIcon
  title: string
  description?: string | null
  time: string
  color: Accent
}>()

const iconClasses: Record<Accent, string> = {
  emerald: 'bg-chart-emerald-soft text-chart-emerald',
  teal: 'bg-chart-teal-soft text-chart-teal',
  sky: 'bg-chart-sky-soft text-chart-sky',
  amber: 'bg-chart-amber-soft text-chart-amber',
  violet: 'bg-chart-violet-soft text-chart-violet',
  rose: 'bg-chart-rose-soft text-chart-rose',
}
</script>

<template>
  <article class="flex items-start gap-3 rounded-2xl p-2 transition duration-200 hover:bg-surface-muted">
    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full" :class="iconClasses[color]">
      <component :is="icon" class="h-4 w-4" />
    </span>
    <div class="min-w-0 flex-1">
      <div class="flex items-start justify-between gap-3">
        <strong class="truncate text-sm font-semibold text-foreground">{{ title }}</strong>
        <span class="shrink-0 text-xs text-muted">{{ time }}</span>
      </div>
      <p class="mt-1 line-clamp-2 text-xs leading-5 text-muted">{{ description || 'Atividade registrada no sistema.' }}</p>
    </div>
  </article>
</template>
