<script setup lang="ts">
import { Bell, ChevronDown, LogOut, Menu, Search } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { listarNotificacoes } from '@/services/notifications'

defineEmits<{
  menu: []
}>()

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const unreadCount = ref(0)
const routesWithTableSearch = ['patients', 'agenda', 'financeiro']
const showGlobalSearch = computed(() => !routesWithTableSearch.includes(String(route.name)))
let notificationTimer: ReturnType<typeof setInterval> | null = null

async function logout() {
  await auth.logout()
  router.push('/login')
}

async function loadNotifications() {
  try {
    const response = await listarNotificacoes()
    unreadCount.value = response.unread_count
  } catch {
    unreadCount.value = 0
  }
}

onMounted(() => {
  loadNotifications()
  notificationTimer = setInterval(loadNotifications, 30000)
  window.addEventListener('focus', loadNotifications)
})

onBeforeUnmount(() => {
  if (notificationTimer) clearInterval(notificationTimer)
  window.removeEventListener('focus', loadNotifications)
})

watch(() => route.fullPath, loadNotifications)
</script>

<template>
  <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border bg-surface/90 px-4 backdrop-blur-xl lg:px-6">
    <div class="flex min-w-0 flex-1 items-center gap-3">
      <button
        class="grid h-10 w-10 place-items-center rounded-xl border border-border text-muted hover:bg-surface-muted lg:hidden"
        type="button"
        aria-label="Abrir menu"
        @click="$emit('menu')"
      >
        <Menu class="h-5 w-5" />
      </button>

      <label v-if="showGlobalSearch" class="relative w-full max-w-xl">
        <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
        <span class="absolute right-3 top-1/2 hidden -translate-y-1/2 rounded-lg border border-border bg-surface px-2 py-1 text-[11px] font-semibold text-muted sm:inline-flex">
          ⌘K
        </span>
      <input
        class="h-11 w-full rounded-full border border-border bg-surface-muted pl-11 pr-16 text-sm text-foreground outline-none transition placeholder:text-muted focus:border-brand focus:ring-4 focus:ring-brand-ring/40"
        placeholder="Buscar pacientes, agenda ou documentos"
        type="search"
      />
      </label>
    </div>

    <div class="ml-4 flex items-center gap-2">
      <button
        class="relative grid h-10 w-10 place-items-center rounded-xl border border-border text-muted transition hover:bg-surface-muted hover:text-foreground"
        title="Notificações"
        aria-label="Abrir notificações"
        @click="router.push('/notificacoes')"
      >
        <Bell class="h-4 w-4" />
        <span
          v-if="unreadCount"
          class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-chart-rose px-1 text-[10px] font-bold text-white"
        >
          {{ unreadCount }}
        </span>
      </button>
      <button class="hidden items-center gap-3 rounded-2xl border border-border bg-surface px-3 py-2 transition hover:bg-surface-muted md:flex" type="button">
        <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-soft text-sm font-bold text-brand-strong">
          {{ (auth.user?.name ?? 'U').slice(0, 1).toUpperCase() }}
        </span>
        <span class="text-left">
          <span class="block text-sm font-bold text-foreground">{{ auth.user?.name ?? 'Usuário' }}</span>
          <span class="block text-xs text-muted">Administrador</span>
        </span>
        <ChevronDown class="h-4 w-4 text-muted" />
      </button>
      <button
        class="grid h-10 w-10 place-items-center rounded-xl border border-border text-muted transition hover:bg-surface-muted hover:text-foreground"
        title="Sair"
        aria-label="Sair"
        @click="logout"
      >
        <LogOut class="h-4 w-4" />
      </button>
    </div>
  </header>
</template>
