<script setup lang="ts">
import { MoreHorizontal } from 'lucide-vue-next'

type Category = 'Retorno' | 'Consulta' | 'Exame' | 'Teleconsulta' | string
type Status = 'scheduled' | 'confirmed' | 'completed' | 'canceled' | 'no_show'

const props = defineProps<{
  time: string
  patientName: string
  professional?: string | null
  category: Category
  status: Status
}>()

const tagClasses: Record<string, string> = {
  Retorno: 'bg-chart-emerald-soft text-chart-emerald',
  Consulta: 'bg-chart-sky-soft text-chart-sky',
  Exame: 'bg-chart-amber-soft text-chart-amber',
  Teleconsulta: 'bg-chart-violet-soft text-chart-violet',
}

const dotClasses: Record<Status, string> = {
  scheduled: 'bg-chart-sky',
  confirmed: 'bg-brand',
  completed: 'bg-chart-emerald',
  canceled: 'bg-chart-rose',
  no_show: 'bg-chart-amber',
}

function initials(name: string) {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}
</script>

<template>
  <article class="grid grid-cols-[64px_1fr_auto] items-center gap-4 rounded-2xl px-3 py-3 transition duration-200 hover:bg-surface-muted">
    <div class="text-sm font-semibold text-muted">{{ time }}</div>

    <div class="relative flex items-center gap-3">
      <span class="absolute -left-6 h-full w-px bg-border" aria-hidden="true" />
      <span class="absolute -left-[27px] h-2.5 w-2.5 rounded-full ring-4 ring-surface" :class="dotClasses[status]" aria-hidden="true" />
      <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-soft text-sm font-bold text-brand-strong">
        {{ initials(props.patientName) }}
      </span>
      <div class="min-w-0">
        <strong class="block truncate text-sm font-semibold text-foreground">{{ patientName }}</strong>
        <span class="block truncate text-xs text-muted">{{ professional || 'Profissional não informado' }}</span>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <span class="rounded-xl px-2.5 py-1 text-xs font-semibold" :class="tagClasses[category] ?? 'bg-surface-muted text-muted'">
        {{ category }}
      </span>
      <button class="grid h-8 w-8 place-items-center rounded-xl text-muted transition hover:bg-surface hover:text-foreground" aria-label="Mais opções">
        <MoreHorizontal class="h-4 w-4" />
      </button>
    </div>
  </article>
</template>
