<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import { carregarDashboard, type DashboardData } from '@/services/dashboard'

const dashboard = ref<DashboardData | null>(null)
const loading = ref(false)
const error = ref('')

const moneyFormatter = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
})

const metrics = computed(() => {
  const data = dashboard.value

  return [
    { label: 'Pacientes ativos', value: String(data?.metrics.active_patients ?? 0), change: 'base atual' },
    {
      label: 'Consultas hoje',
      value: String(data?.metrics.appointments_today ?? 0),
      change: `${data?.metrics.pending_today ?? 0} pendentes`,
    },
    {
      label: 'Receita mensal',
      value: moneyFormatter.format(data?.metrics.monthly_revenue ?? 0),
      change: `${moneyFormatter.format(data?.metrics.monthly_expenses ?? 0)} despesas`,
    },
    { label: 'Taxa de ocupação', value: `${data?.metrics.occupancy_rate ?? 0}%`, change: 'capacidade diária' },
  ]
})

function formatTime(value: string) {
  return new Date(value.replace(' ', 'T')).toLocaleTimeString('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
  })
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
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#0F172A]">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Resumo operacional da clínica</p>
      </div>
      <AppBadge tone="success">Clínica em operação</AppBadge>
    </div>

    <p v-if="error" class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</p>
    <div v-if="loading" class="rounded-lg border border-[#E2E8F0] bg-white p-8 text-center text-sm text-slate-500">
      Carregando dashboard...
    </div>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <AppCard v-for="metric in metrics" :key="metric.label" class="p-5">
        <p class="text-sm font-medium text-slate-500">{{ metric.label }}</p>
        <strong class="mt-3 block text-2xl font-bold text-[#0F172A]">{{ metric.value }}</strong>
        <span class="mt-3 inline-block text-xs font-semibold text-emerald-700">{{ metric.change }}</span>
      </AppCard>
    </section>

    <section class="mt-6 grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
      <AppCard class="p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-semibold">Agenda do dia</h2>
          <AppBadge>Hoje</AppBadge>
        </div>
        <div class="grid gap-3">
          <div v-if="(dashboard?.schedule.length ?? 0) === 0" class="rounded-lg border border-[#E2E8F0] px-4 py-6 text-center text-sm text-slate-500">
            Nenhum agendamento para hoje.
          </div>
          <div
            v-for="item in dashboard?.schedule ?? []"
            :key="item.id"
            class="flex items-center justify-between rounded-lg border border-[#E2E8F0] px-4 py-3"
          >
            <div>
              <strong class="text-sm text-[#0F172A]">{{ item.patient?.nome ?? item.title }}</strong>
              <p class="text-xs text-slate-500">{{ item.type }}</p>
            </div>
            <span class="text-sm font-semibold text-slate-700">{{ formatTime(item.starts_at) }}</span>
          </div>
        </div>
      </AppCard>

      <div class="grid gap-4">
        <AppCard class="p-5">
          <h2 class="mb-4 text-lg font-semibold">Indicadores da clínica</h2>
          <div class="grid gap-3">
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Tempo médio de espera</span>
              <strong>{{ dashboard?.indicators.average_wait_minutes ?? 0 }} min</strong>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Satisfação</span>
              <strong>{{ dashboard?.indicators.satisfaction_rate ?? 0 }}%</strong>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Faltas no mês</span>
              <strong>{{ dashboard?.indicators.no_show_rate ?? 0 }}%</strong>
            </div>
          </div>
        </AppCard>

        <AppCard class="p-5">
          <h2 class="mb-4 text-lg font-semibold">Atividades recentes</h2>
          <ul class="grid gap-3 text-sm text-slate-600">
            <li v-if="(dashboard?.activities.length ?? 0) === 0" class="rounded-lg bg-slate-50 px-3 py-2">
              Nenhuma atividade recente.
            </li>
            <li v-for="activity in dashboard?.activities ?? []" :key="activity.id" class="rounded-lg bg-slate-50 px-3 py-2">
              {{ activity.title }}
            </li>
          </ul>
        </AppCard>
      </div>
    </section>
  </AppLayout>
</template>
