<script setup lang="ts">
import { computed } from 'vue'
import type { LucideIcon } from 'lucide-vue-next'

type Accent = 'emerald' | 'teal' | 'sky' | 'amber' | 'violet' | 'rose'

const props = defineProps<{
  icon: LucideIcon
  label: string
  value: number
  color: Accent
  maxValue?: number
  suffix?: string
}>()

const progress = computed(() => {
  const max = props.maxValue || 100

  return `${Math.max(0, Math.min(100, (props.value / max) * 100))}%`
})

const classes: Record<Accent, { icon: string; bar: string }> = {
  emerald: { icon: 'bg-chart-emerald-soft text-chart-emerald', bar: 'bg-chart-emerald' },
  teal: { icon: 'bg-chart-teal-soft text-chart-teal', bar: 'bg-chart-teal' },
  sky: { icon: 'bg-chart-sky-soft text-chart-sky', bar: 'bg-chart-sky' },
  amber: { icon: 'bg-chart-amber-soft text-chart-amber', bar: 'bg-chart-amber' },
  violet: { icon: 'bg-chart-violet-soft text-chart-violet', bar: 'bg-chart-violet' },
  rose: { icon: 'bg-chart-rose-soft text-chart-rose', bar: 'bg-chart-rose' },
}
</script>

<template>
  <div class="grid gap-2">
    <div class="flex items-center justify-between gap-3">
      <div class="flex min-w-0 items-center gap-3">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl" :class="classes[color].icon">
          <component :is="icon" class="h-4 w-4" />
        </span>
        <span class="truncate text-sm font-medium text-foreground">{{ label }}</span>
      </div>
      <strong class="text-sm font-bold text-foreground">{{ value }}{{ suffix ?? '' }}</strong>
    </div>
    <div class="h-2 rounded-full bg-surface-muted">
      <div class="h-2 rounded-full transition-all duration-500" :class="classes[color].bar" :style="{ width: progress }" />
    </div>
  </div>
</template>
