<script setup lang="ts">
import AppBadge from '@/components/ui/AppBadge.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppLayout from '@/components/layout/AppLayout.vue'

const metrics = [
  { label: 'Pacientes ativos', value: '1.248', change: '+12%' },
  { label: 'Consultas hoje', value: '34', change: '8 pendentes' },
  { label: 'Receita mensal', value: 'R$ 86.420', change: '+18%' },
  { label: 'Taxa de ocupação', value: '78%', change: 'Alta' },
]

const schedule = [
  { time: '08:30', patient: 'Marina Costa', type: 'Retorno' },
  { time: '09:15', patient: 'João Martins', type: 'Consulta' },
  { time: '10:40', patient: 'Aline Rocha', type: 'Exame' },
  { time: '11:20', patient: 'Carlos Lima', type: 'Teleconsulta' },
]

const activities = [
  'Paciente Maria Souza cadastrado',
  'Pagamento confirmado no convênio Saúde Mais',
  'Agenda do Dr. Paulo atualizada',
]
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
          <div
            v-for="item in schedule"
            :key="`${item.time}-${item.patient}`"
            class="flex items-center justify-between rounded-lg border border-[#E2E8F0] px-4 py-3"
          >
            <div>
              <strong class="text-sm text-[#0F172A]">{{ item.patient }}</strong>
              <p class="text-xs text-slate-500">{{ item.type }}</p>
            </div>
            <span class="text-sm font-semibold text-slate-700">{{ item.time }}</span>
          </div>
        </div>
      </AppCard>

      <div class="grid gap-4">
        <AppCard class="p-5">
          <h2 class="mb-4 text-lg font-semibold">Indicadores da clínica</h2>
          <div class="grid gap-3">
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Tempo médio de espera</span>
              <strong>14 min</strong>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Satisfação</span>
              <strong>94%</strong>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Faltas no mês</span>
              <strong>6%</strong>
            </div>
          </div>
        </AppCard>

        <AppCard class="p-5">
          <h2 class="mb-4 text-lg font-semibold">Atividades recentes</h2>
          <ul class="grid gap-3 text-sm text-slate-600">
            <li v-for="activity in activities" :key="activity" class="rounded-lg bg-slate-50 px-3 py-2">
              {{ activity }}
            </li>
          </ul>
        </AppCard>
      </div>
    </section>
  </AppLayout>
</template>
