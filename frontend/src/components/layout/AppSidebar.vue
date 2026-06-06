<script setup lang="ts">
import {
  Bell,
  CalendarDays,
  Headphones,
  LayoutDashboard,
  Plus,
  Settings,
  Users,
  WalletCards,
  X,
} from 'lucide-vue-next'

defineProps<{
  mobileOpen?: boolean
}>()

defineEmits<{
  close: []
}>()

const items = [
  { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
  { label: 'Pacientes', to: '/patients', icon: Users },
  { label: 'Agenda', to: '/agenda', icon: CalendarDays },
  { label: 'Financeiro', to: '/financeiro', icon: WalletCards },
  { label: 'Notificações', to: '/notificacoes', icon: Bell },
  { label: 'Configurações', to: '/configuracoes', icon: Settings },
]
</script>

<template>
  <div
    v-if="mobileOpen"
    class="fixed inset-0 z-40 bg-foreground/30 backdrop-blur-sm lg:hidden"
    aria-hidden="true"
    @click="$emit('close')"
  />

  <aside
    class="fixed inset-y-0 left-0 z-50 flex w-[min(280px,calc(100vw-24px))] flex-col overflow-y-auto border-r border-border bg-surface px-4 py-5 shadow-sm transition-transform duration-200 lg:sticky lg:top-0 lg:z-auto lg:min-h-screen lg:w-[260px] lg:translate-x-0"
    :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    aria-label="Navegação principal"
  >
    <div class="mb-8 flex items-center justify-between">
      <RouterLink to="/dashboard" class="flex items-center gap-3 px-1" @click="$emit('close')">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-brand-soft text-brand-strong">
          <Plus class="h-5 w-5 stroke-[3]" />
        </span>
        <span>
          <strong class="block text-sm font-bold tracking-tight text-foreground">Clinic SaaS</strong>
          <small class="text-xs font-medium text-muted">Gestão médica</small>
        </span>
      </RouterLink>
      <button
        class="grid h-9 w-9 place-items-center rounded-xl text-muted hover:bg-surface-muted lg:hidden"
        type="button"
        aria-label="Fechar menu"
        @click="$emit('close')"
      >
        <X class="h-4 w-4" />
      </button>
    </div>

    <nav class="grid gap-1" aria-label="Seções">
      <RouterLink
        v-for="item in items"
        :key="item.label"
        :to="item.to"
        class="border-l-2 border-transparent flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-muted transition duration-200 hover:bg-surface-muted hover:text-foreground"
        active-class="!border-brand !bg-brand-soft !text-brand-strong"
        @click="$emit('close')"
      >
        <component :is="item.icon" class="h-4 w-4" />
        {{ item.label }}
      </RouterLink>
    </nav>

    <div class="mt-auto hidden rounded-2xl border border-border bg-surface-muted p-4 sm:block">
      <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-soft text-brand-strong">
        <Headphones class="h-4 w-4" />
      </span>
      <strong class="mt-3 block text-sm text-foreground">Precisa de ajuda?</strong>
      <p class="mt-1 text-xs leading-5 text-muted">Nosso time está pronto para apoiar sua operação.</p>
      <button class="mt-4 h-9 w-full rounded-[10px] bg-brand text-sm font-bold text-white transition hover:bg-brand-strong">
        Abrir suporte
      </button>
    </div>
  </aside>
</template>
