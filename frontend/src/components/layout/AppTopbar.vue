<script setup lang="ts">
import { Bell, LogOut, Search, UserCircle } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <header class="flex h-16 items-center justify-between border-b border-[#E2E8F0] bg-white px-4 lg:px-6">
    <label class="relative w-full max-w-md">
      <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
      <input
        class="h-10 w-full rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] pl-10 pr-3 text-sm outline-none focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20"
        placeholder="Buscar pacientes, agenda ou documentos"
        type="search"
      />
    </label>

    <div class="ml-4 flex items-center gap-2">
      <button
        class="grid h-10 w-10 place-items-center rounded-lg border border-[#E2E8F0] text-slate-500 hover:bg-slate-50"
        title="Notificações"
        @click="router.push('/notificacoes')"
      >
        <Bell class="h-4 w-4" />
      </button>
      <div class="hidden items-center gap-2 rounded-lg border border-[#E2E8F0] px-3 py-2 md:flex">
        <UserCircle class="h-5 w-5 text-slate-500" />
        <span class="text-sm font-medium text-[#0F172A]">{{ auth.user?.name ?? 'Usuário' }}</span>
      </div>
      <button
        class="grid h-10 w-10 place-items-center rounded-lg border border-[#E2E8F0] text-slate-500 hover:bg-slate-50"
        title="Sair"
        @click="logout"
      >
        <LogOut class="h-4 w-4" />
      </button>
    </div>
  </header>
</template>
