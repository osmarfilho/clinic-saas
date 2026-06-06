<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { BellRing, CheckCheck } from 'lucide-vue-next'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import {
  listarNotificacoes,
  marcarNotificacaoComoLida,
  marcarTodasComoLidas,
  type ClinicNotification,
} from '@/services/notifications'

const notifications = ref<ClinicNotification[]>([])
const unreadCount = ref(0)
const loading = ref(false)
const error = ref('')

function formatDate(value: string) {
  return new Date(value).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function tone(type: ClinicNotification['type']) {
  if (type === 'success') return 'success'
  if (type === 'danger' || type === 'warning') return 'danger'
  return 'neutral'
}

async function loadNotifications() {
  loading.value = true
  error.value = ''

  try {
    const response = await listarNotificacoes()
    notifications.value = response.notifications.data
    unreadCount.value = response.unread_count
  } catch {
    error.value = 'Não foi possível carregar as notificações.'
  } finally {
    loading.value = false
  }
}

async function readNotification(notification: ClinicNotification) {
  if (notification.read_at) return

  await marcarNotificacaoComoLida(notification.id)
  await loadNotifications()
}

async function readAll() {
  await marcarTodasComoLidas()
  await loadNotifications()
}

onMounted(loadNotifications)
</script>

<template>
  <AppLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#0F172A]">Notificações</h1>
        <p class="mt-1 text-sm text-slate-500">Alertas operacionais, pagamentos e alterações recentes</p>
      </div>
      <AppButton variant="secondary" @click="readAll">
        <CheckCheck class="h-4 w-4" />
        Marcar todas como lidas
      </AppButton>
    </div>

    <p v-if="error" class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</p>

    <AppCard class="mb-4 flex items-center justify-between p-5">
      <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-lg bg-[#6FF6A5]/30 text-[#0F172A]">
          <BellRing class="h-5 w-5" />
        </span>
        <div>
          <strong class="block text-[#0F172A]">{{ unreadCount }} não lidas</strong>
          <span class="text-sm text-slate-500">{{ notifications.length }} notificações listadas</span>
        </div>
      </div>
      <AppBadge :tone="unreadCount ? 'danger' : 'success'">{{ unreadCount ? 'Atenção' : 'Tudo em dia' }}</AppBadge>
    </AppCard>

    <div v-if="loading" class="rounded-lg border border-[#E2E8F0] bg-white p-8 text-center text-sm text-slate-500">
      Carregando notificações...
    </div>

    <section v-else class="grid gap-3">
      <AppCard v-if="notifications.length === 0" class="p-8 text-center text-sm text-slate-500">
        Nenhuma notificação encontrada.
      </AppCard>
      <button
        v-for="notification in notifications"
        :key="notification.id"
        class="rounded-lg border border-[#E2E8F0] bg-white p-4 text-left shadow-sm transition hover:border-[#6FF6A5]"
        @click="readNotification(notification)"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <strong class="text-[#0F172A]">{{ notification.title }}</strong>
            <p class="mt-1 text-sm text-slate-500">{{ notification.body || 'Sem detalhes adicionais.' }}</p>
          </div>
          <div class="flex items-center gap-2">
            <AppBadge :tone="tone(notification.type)">{{ notification.read_at ? 'Lida' : 'Nova' }}</AppBadge>
            <span class="text-xs text-slate-500">{{ formatDate(notification.created_at) }}</span>
          </div>
        </div>
      </button>
    </section>
  </AppLayout>
</template>
