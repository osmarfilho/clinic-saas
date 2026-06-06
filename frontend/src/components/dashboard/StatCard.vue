<script setup lang="ts">
import { computed } from 'vue'
import { ArrowDownRight, ArrowUpRight, type LucideIcon } from 'lucide-vue-next'

type Accent = 'emerald' | 'teal' | 'sky' | 'amber' | 'violet' | 'rose'

const props = defineProps<{
  icon: LucideIcon
  label: string
  value: string
  trend: number
  helper: string
  sparklineData: number[]
  color: Accent
  loading?: boolean
}>()

const colorClasses: Record<Accent, { icon: string; stroke: string; fill: string }> = {
  emerald: {
    icon: 'bg-chart-emerald-soft text-chart-emerald',
    stroke: 'stroke-chart-emerald',
    fill: 'fill-chart-emerald-soft',
  },
  teal: {
    icon: 'bg-chart-teal-soft text-chart-teal',
    stroke: 'stroke-chart-teal',
    fill: 'fill-chart-teal-soft',
  },
  sky: {
    icon: 'bg-chart-sky-soft text-chart-sky',
    stroke: 'stroke-chart-sky',
    fill: 'fill-chart-sky-soft',
  },
  amber: {
    icon: 'bg-chart-amber-soft text-chart-amber',
    stroke: 'stroke-chart-amber',
    fill: 'fill-chart-amber-soft',
  },
  violet: {
    icon: 'bg-chart-violet-soft text-chart-violet',
    stroke: 'stroke-chart-violet',
    fill: 'fill-chart-violet-soft',
  },
  rose: {
    icon: 'bg-chart-rose-soft text-chart-rose',
    stroke: 'stroke-chart-rose',
    fill: 'fill-chart-rose-soft',
  },
}

const points = computed(() => {
  const values = props.sparklineData.length ? props.sparklineData : [0, 0]
  const min = Math.min(...values)
  const max = Math.max(...values)
  const range = max - min || 1

  return values.map((value, index) => {
    const x = (index / (values.length - 1 || 1)) * 160
    const y = 52 - ((value - min) / range) * 42

    return [x, y]
  })
})

const linePath = computed(() => points.value.map(([x, y], index) => `${index === 0 ? 'M' : 'L'} ${x} ${y}`).join(' '))
const areaPath = computed(() => `${linePath.value} L 160 58 L 0 58 Z`)
</script>

<template>
  <section
    class="group rounded-2xl border border-border bg-surface p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:scale-[1.01] hover:shadow-md sm:p-6"
  >
    <div v-if="loading" class="animate-pulse">
      <div class="h-11 w-11 rounded-full bg-surface-muted" />
      <div class="mt-5 h-3 w-28 rounded-full bg-surface-muted" />
      <div class="mt-4 h-8 w-24 rounded-full bg-surface-muted" />
      <div class="mt-5 h-12 rounded-xl bg-surface-muted" />
    </div>

    <template v-else>
      <div class="flex items-start justify-between">
        <span class="grid h-11 w-11 place-items-center rounded-full" :class="colorClasses[color].icon">
          <component :is="icon" class="h-5 w-5" />
        </span>
        <span
          class="inline-flex items-center gap-1 rounded-xl px-2 py-1 text-xs font-semibold"
          :class="trend >= 0 ? 'bg-brand-soft text-brand-strong' : 'bg-chart-rose-soft text-chart-rose'"
        >
          <ArrowUpRight v-if="trend >= 0" class="h-3.5 w-3.5" />
          <ArrowDownRight v-else class="h-3.5 w-3.5" />
          {{ Math.abs(trend) }}%
        </span>
      </div>

      <div class="mt-5">
        <p class="text-sm font-medium text-muted">{{ label }}</p>
        <strong class="mt-2 block text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{{ value }}</strong>
        <p class="mt-2 text-xs text-muted">{{ helper }}</p>
      </div>

      <svg class="mt-5 h-16 w-full overflow-visible" viewBox="0 0 160 60" aria-hidden="true">
        <path :d="areaPath" class="opacity-80" :class="colorClasses[color].fill" />
        <path :d="linePath" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" :class="colorClasses[color].stroke" />
      </svg>
    </template>
  </section>
</template>
