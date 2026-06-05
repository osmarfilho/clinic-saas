<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppButton from '@/components/ui/AppButton.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const email = ref('test@example.com')
const password = ref('password')
const error = ref('')

async function submit() {
  error.value = ''

  try {
    await auth.login(email.value, password.value)
    router.push(String(route.query.redirect ?? '/dashboard'))
  } catch {
    error.value = 'Não foi possível entrar com essas credenciais.'
  }
}
</script>

<template>
  <main class="grid min-h-screen place-items-center bg-[#F8FAFC] p-4">
    <AppCard class="w-full max-w-md p-6">
      <div class="mb-6">
        <div class="mb-4 grid h-12 w-12 place-items-center rounded-lg bg-[#6FF6A5] text-lg font-bold">
          C
        </div>
        <h1 class="text-2xl font-bold text-[#0F172A]">Clinic SaaS</h1>
        <p class="mt-1 text-sm text-slate-500">Acesse o painel da clínica</p>
      </div>

      <form class="grid gap-4" @submit.prevent="submit">
        <AppInput v-model="email" label="E-mail" type="email" required />
        <AppInput v-model="password" label="Senha" type="password" required />
        <p v-if="error" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
        <AppButton type="submit" :disabled="auth.loading">
          {{ auth.loading ? 'Entrando...' : 'Entrar' }}
        </AppButton>
      </form>
    </AppCard>
  </main>
</template>
