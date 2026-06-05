<script setup lang="ts">
import { CalendarDays, LayoutDashboard, Settings, Users, WalletCards } from 'lucide-vue-next'

const items = [
  { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
  { label: 'Pacientes', to: '/patients', icon: Users },
  { label: 'Agenda', to: '/agenda', icon: CalendarDays },
  { label: 'Financeiro', to: '/financeiro', icon: WalletCards },
  { label: 'Configurações', to: '/configuracoes', icon: Settings },
]
</script>

<template>
  <aside class="hidden min-h-screen w-64 border-r border-[#E2E8F0] bg-white px-4 py-5 lg:block">
    <RouterLink to="/dashboard" class="mb-8 flex items-center gap-3 px-2">
      <span class="grid h-10 w-10 place-items-center rounded-lg bg-[#6FF6A5] font-bold text-[#0F172A]">
        C
      </span>
      <span>
        <strong class="block text-base text-[#0F172A]">Clinic SaaS</strong>
        <small class="text-xs text-slate-500">Gestão médica</small>
      </span>
    </RouterLink>

    <nav class="grid gap-1">
      <RouterLink
        v-for="item in items"
        :key="item.label"
        :to="item.to"
        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-[#0F172A]"
        active-class="bg-[#6FF6A5]/20 text-[#0F172A]"
      >
        <component :is="item.icon" class="h-4 w-4" />
        {{ item.label }}
      </RouterLink>
    </nav>
  </aside>
</template>
