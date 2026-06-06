<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  Activity,
  CalendarDays,
  CheckCircle2,
  Clock3,
  DollarSign,
  FileText,
  HeartPulse,
  PieChart,
  RefreshCcw,
  TrendingDown,
  Users,
} from 'lucide-vue-next'
import ActivityItem from '@/components/dashboard/ActivityItem.vue'
import AppointmentRow from '@/components/dashboard/AppointmentRow.vue'
import IndicatorBar from '@/components/dashboard/IndicatorBar.vue'
import PageHeader from '@/components/dashboard/PageHeader.vue'
import StatCard from '@/components/dashboard/StatCard.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import { carregarDashboard, type DashboardData } from '@/services/dashboard'

const dashboard = ref<DashboardData | null>(null)
const loading = ref(false)
const error = ref('')

const moneyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
  maximumFractionDigits: 0,
})

const stats = computed(() => {
  const data = dashboard.value

  return [
    {
      icon: Users,
      label: 'Pacientes ativos',
      value: String(data?.metrics.active_patients ?? 0),
      trend: 12,
      helper: 'vs. mês anterior',
      sparklineData: [18, 22, 21, 25, 29, 31, 30, 34, 36, data?.metrics.active_patients ?? 28],
      color: 'emerald' as const,
    },
    {
      icon: CalendarDays,
      label: 'Consultas hoje',
      value: String(data?.metrics.appointments_today ?? 0),
      trend: data?.metrics.pending_today ? 8 : 2,
      helper: `${data?.metrics.pending_today ?? 0} pendentes`,
      sparklineData: [4, 6, 5, 8, 7, 10, 9, 12, 11, data?.metrics.appointments_today ?? 0],
      color: 'teal' as const,
    },
    {
      icon: DollarSign,
      label: 'Receita mensal',
      value: moneyFormatter.format(data?.metrics.monthly_revenue ?? 0),
      trend: 18,
      helper: `${moneyFormatter.format(data?.metrics.monthly_expenses ?? 0)} despesas`,
      sparklineData: [1200, 1800, 1500, 2300, 2600, 2400, 3100, 3600, 3900, data?.metrics.monthly_revenue ?? 0],
      color: 'emerald' as const,
    },
    {
      icon: PieChart,
      label: 'Taxa de ocupação',
      value: `${data?.metrics.occupancy_rate ?? 0}%`,
      trend: (data?.metrics.occupancy_rate ?? 0) >= 80 ? 5 : 12,
      helper: 'vs. mês anterior',
      sparklineData: [45, 52, 49, 58, 63, 61, 70, 72, 76, data?.metrics.occupancy_rate ?? 0],
      color: 'teal' as const,
    },
  ]
})

const indicators = computed(() => {
  const satisfaction = dashboard.value?.indicators.satisfaction_rate ?? 0
  const noShow = dashboard.value?.indicators.no_show_rate ?? 0
  const occupancy = dashboard.value?.metrics.occupancy_rate ?? 0
  const expenses = dashboard.value?.metrics.monthly_expenses ?? 0
  const revenue = dashboard.value?.metrics.monthly_revenue ?? 0
  const delinquency = revenue > 0 ? Math.min(100, Math.round((expenses / revenue) * 100)) : 0

  return [
    { icon: HeartPulse, label: 'Satisfação dos pacientes', value: satisfaction, color: 'emerald' as const },
    { icon: RefreshCcw, label: 'Retornos realizados', value: Math.min(100, occupancy), color: 'teal' as const },
    { icon: TrendingDown, label: 'Cancelamentos e faltas', value: noShow, color: 'rose' as const },
    { icon: DollarSign, label: 'Inadimplência estimada', value: delinquency, color: 'amber' as const },
  ]
})

function formatTime(value: string) {
  return new Date(value.replace(' ', 'T')).toLocaleTimeString('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatRelative(value: string) {
  const date = new Date(value)
  const diffMinutes = Math.max(0, Math.round((Date.now() - date.getTime()) / 60000))

  if (diffMinutes < 1) return 'agora'
  if (diffMinutes < 60) return `${diffMinutes} min atrás`

  const diffHours = Math.round(diffMinutes / 60)
  if (diffHours < 24) return `${diffHours} h atrás`

  return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

function activityIcon(type: string) {
  if (type === 'success') return CheckCircle2
  if (type === 'warning') return Clock3
  if (type === 'danger') return TrendingDown
  return FileText
}

function activityColor(type: string) {
  if (type === 'success') return 'emerald'
  if (type === 'warning') return 'amber'
  if (type === 'danger') return 'rose'
  return 'teal'
}

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    dashboard.value = await carregarDashboard()
  } catch {
    error.value = 'Não foi possível carregar o dashboard.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <AppLayout>
    <PageHeader title="Dashboard" subtitle="Resumo operacional da clínica" />

    <p v-if="error" class="mb-6 rounded-2xl border border-chart-rose-soft bg-chart-rose-soft px-4 py-3 text-sm font-medium text-chart-rose">
      {{ error }}
    </p>

    <section class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard
        v-for="stat in stats"
        :key="stat.label"
        :icon="stat.icon"
        :label="stat.label"
        :value="stat.value"
        :trend="stat.trend"
        :helper="stat.helper"
        :sparkline-data="stat.sparklineData"
        :color="stat.color"
        :loading="loading"
      />
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[2fr_1fr]">
      <section class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <header class="mb-5 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-surface-muted text-muted">
              <CalendarDays class="h-4 w-4" />
            </span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-foreground">Agenda do dia</h2>
              <p class="text-xs text-muted">{{ dashboard?.schedule.length ?? 0 }} compromissos listados</p>
            </div>
          </div>
          <RouterLink
            to="/agenda"
            class="rounded-[10px] border border-border px-3 py-2 text-sm font-semibold text-foreground transition hover:bg-surface-muted"
          >
            Ver agenda completa
          </RouterLink>
        </header>

        <div v-if="loading" class="grid gap-3">
          <div v-for="item in 5" :key="item" class="h-16 animate-pulse rounded-2xl bg-surface-muted" />
        </div>

        <div v-else-if="(dashboard?.schedule.length ?? 0) === 0" class="grid min-h-72 place-items-center rounded-2xl border border-dashed border-border bg-surface-muted p-8 text-center">
          <div>
            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-brand-soft text-brand-strong">
              <CalendarDays class="h-5 w-5" />
            </span>
            <strong class="mt-4 block text-sm text-foreground">Nenhum agendamento hoje</strong>
            <p class="mt-1 text-sm text-muted">A agenda do dia está livre.</p>
          </div>
        </div>

        <div v-else class="grid gap-1">
          <AppointmentRow
            v-for="item in dashboard?.schedule ?? []"
            :key="item.id"
            :time="formatTime(item.starts_at)"
            :patient-name="item.patient?.nome ?? item.title"
            :professional="item.professional"
            :category="item.type"
            :status="item.status"
          />
        </div>
      </section>

      <div class="grid gap-6">
        <section class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
          <header class="mb-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="grid h-9 w-9 place-items-center rounded-xl bg-surface-muted text-muted">
                <Activity class="h-4 w-4" />
              </span>
              <h2 class="text-base font-bold tracking-tight text-foreground">Indicadores da clínica</h2>
            </div>
          </header>

          <div v-if="loading" class="grid gap-5">
            <div v-for="item in 4" :key="item" class="h-12 animate-pulse rounded-2xl bg-surface-muted" />
          </div>
          <div v-else class="grid gap-5">
            <IndicatorBar
              v-for="indicator in indicators"
              :key="indicator.label"
              :icon="indicator.icon"
              :label="indicator.label"
              :value="indicator.value"
              :color="indicator.color"
            />
          </div>
        </section>

        <section class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
          <header class="mb-5 flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-surface-muted text-muted">
              <Clock3 class="h-4 w-4" />
            </span>
            <h2 class="text-base font-bold tracking-tight text-foreground">Atividades recentes</h2>
          </header>

          <div v-if="loading" class="grid gap-3">
            <div v-for="item in 4" :key="item" class="h-14 animate-pulse rounded-2xl bg-surface-muted" />
          </div>
          <div v-else-if="(dashboard?.activities.length ?? 0) === 0" class="rounded-2xl border border-dashed border-border bg-surface-muted p-6 text-center text-sm text-muted">
            Nenhuma atividade recente.
          </div>
          <div v-else class="grid gap-1">
            <ActivityItem
              v-for="activity in dashboard?.activities ?? []"
              :key="activity.id"
              :icon="activityIcon(activity.type)"
              :title="activity.title"
              :description="activity.body"
              :time="formatRelative(activity.created_at)"
              :color="activityColor(activity.type)"
            />
          </div>
        </section>
      </div>
    </section>
  </AppLayout>
</template>
