<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { isAxiosError } from 'axios'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowRight,
  CalendarDays,
  Eye,
  EyeOff,
  Loader2,
  Lock,
  Mail,
  ShieldCheck,
  TrendingUp,
  Users,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const email = ref('test@example.com')
const password = ref('password')
const showPassword = ref(false)
const error = ref('')
const fieldErrors = ref({
  email: '',
  password: '',
})

const benefits = [
  {
    icon: CalendarDays,
    title: 'Agenda inteligente com lembretes automáticos',
  },
  {
    icon: Users,
    title: 'Prontuário eletrônico seguro e centralizado',
  },
  {
    icon: TrendingUp,
    title: 'Relatórios financeiros em tempo real',
  },
]

async function submit() {
  error.value = ''
  fieldErrors.value = {
    email: '',
    password: '',
  }

  if (!email.value.trim()) {
    fieldErrors.value.email = 'Informe seu e-mail.'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    fieldErrors.value.email = 'Informe um e-mail válido.'
  }

  if (!password.value) {
    fieldErrors.value.password = 'Informe sua senha.'
  }

  if (fieldErrors.value.email || fieldErrors.value.password) {
    error.value = 'Preencha os campos obrigatórios para entrar.'
    return
  }

  try {
    await auth.login(email.value, password.value)
    router.push(String(route.query.redirect ?? '/dashboard'))
  } catch (requestError) {
    if (!isAxiosError(requestError)) {
      error.value = 'Não foi possível entrar. Tente novamente.'
      return
    }

    if (!requestError.response) {
      error.value = 'Backend indisponível. Verifique se a API está online.'
      return
    }

    if ([401, 422].includes(requestError.response.status)) {
      error.value = 'Credenciais inválidas. Confira e-mail e senha.'
      fieldErrors.value.email = ' '
      fieldErrors.value.password = ' '
      return
    }

    error.value = requestError.response.data?.message ?? 'Não foi possível entrar. Tente novamente.'
  }
}

onMounted(() => {
  document.title = 'Entrar | Clinic SaaS'

  const description = 'Acesse o painel da sua clínica.'
  let meta = document.querySelector('meta[name="description"]')

  if (!meta) {
    meta = document.createElement('meta')
    meta.setAttribute('name', 'description')
    document.head.appendChild(meta)
  }

  meta.setAttribute('content', description)
})
</script>

<template>
  <main class="min-h-screen bg-background text-foreground lg:grid lg:grid-cols-[minmax(0,3fr)_minmax(420px,2fr)]">
    <section class="relative hidden overflow-hidden bg-gradient-to-br from-primary to-clinic-mint px-10 py-8 text-white lg:flex lg:flex-col">
      <div
        class="absolute inset-0 opacity-10"
        style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;"
      />
      <div class="absolute -left-24 top-24 h-72 w-72 rounded-full bg-white/20 blur-3xl" />
      <div class="absolute bottom-10 right-8 h-80 w-80 rounded-full bg-clinic-glow/30 blur-3xl" />
      <div class="absolute right-20 top-10 h-40 w-40 rounded-full border border-white/20" />

      <div class="relative z-10 flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-white/20 text-white backdrop-blur-sm">
          <span class="text-lg font-black">+</span>
        </span>
        <strong class="text-xl font-bold tracking-tight">Clinic SaaS</strong>
      </div>

      <div class="relative z-10 my-auto max-w-3xl">
        <div class="animate-fade-slide-up">
          <h1 class="max-w-2xl text-4xl font-bold leading-tight tracking-tight text-white xl:text-5xl">
            Gerencie sua clínica com inteligência
          </h1>
          <p class="mt-5 max-w-xl text-lg leading-8 text-white/80">
            Agenda, prontuários, financeiro e pacientes em uma única plataforma.
          </p>
        </div>

        <div class="mt-10 grid max-w-2xl gap-4">
          <article
            v-for="(benefit, index) in benefits"
            :key="benefit.title"
            class="animate-fade-slide-up flex items-center gap-4 rounded-xl border border-white/20 bg-white/10 p-4 text-white shadow-sm backdrop-blur-sm"
            :style="{ animationDelay: `${120 + index * 100}ms` }"
          >
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-white/15">
              <component :is="benefit.icon" class="h-5 w-5" />
            </span>
            <strong class="text-sm font-semibold">{{ benefit.title }}</strong>
          </article>
        </div>
      </div>

      <div class="relative z-10 flex items-center gap-4">
        <div class="flex -space-x-3">
          <span class="grid h-10 w-10 place-items-center rounded-full border-2 border-white bg-emerald-100 text-sm font-bold text-primary">A</span>
          <span class="grid h-10 w-10 place-items-center rounded-full border-2 border-white bg-teal-100 text-sm font-bold text-primary">M</span>
          <span class="grid h-10 w-10 place-items-center rounded-full border-2 border-white bg-white text-sm font-bold text-primary">P</span>
        </div>
        <p class="text-sm font-medium text-white/85">+500 clínicas já confiam na Clinic SaaS</p>
      </div>
    </section>

    <section class="flex min-h-screen flex-col bg-background px-6 py-8 lg:min-h-screen lg:px-12">
      <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center">
        <div class="animate-fade-slide-right">
          <div class="mb-8 text-center lg:hidden">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-brand-soft text-brand-strong">
              <span class="text-2xl font-black">+</span>
            </div>
            <strong class="mt-3 block text-xl font-bold">Clinic SaaS</strong>
          </div>

          <div class="mb-8 text-center lg:text-left">
            <h1 class="text-3xl font-bold tracking-tight text-foreground">Bem-vindo de volta</h1>
            <p class="mt-2 text-sm text-muted-foreground">Entre na sua conta para acessar o painel</p>
          </div>

          <form class="grid gap-5" @submit.prevent="submit">
            <div class="grid gap-2">
              <label class="text-sm font-semibold text-foreground" for="email">E-mail</label>
              <div class="relative">
                <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input
                  id="email"
                  v-model="email"
                  class="h-12 w-full rounded-lg border bg-background pl-10 pr-3 text-sm outline-none transition placeholder:text-muted-foreground focus:border-primary focus:ring-4 focus:ring-brand-ring/40"
                  :class="fieldErrors.email ? 'border-chart-rose' : 'border-input'"
                  placeholder="seu@email.com"
                  type="email"
                  autocomplete="email"
                  required
                  :aria-invalid="Boolean(fieldErrors.email)"
                />
              </div>
              <p v-if="fieldErrors.email.trim()" class="text-sm font-medium text-chart-rose">
                {{ fieldErrors.email }}
              </p>
            </div>

            <div class="grid gap-2">
              <label class="text-sm font-semibold text-foreground" for="password">Senha</label>
              <div class="relative">
                <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input
                  id="password"
                  v-model="password"
                  class="h-12 w-full rounded-lg border bg-background pl-10 pr-11 text-sm outline-none transition placeholder:text-muted-foreground focus:border-primary focus:ring-4 focus:ring-brand-ring/40"
                  :class="fieldErrors.password ? 'border-chart-rose' : 'border-input'"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  required
                  :aria-invalid="Boolean(fieldErrors.password)"
                />
                <button
                  class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-muted-foreground transition hover:bg-surface-muted hover:text-foreground"
                  type="button"
                  :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                  @click="showPassword = !showPassword"
                >
                  <EyeOff v-if="showPassword" class="h-4 w-4" />
                  <Eye v-else class="h-4 w-4" />
                </button>
              </div>
              <p v-if="fieldErrors.password.trim()" class="text-sm font-medium text-chart-rose">
                {{ fieldErrors.password }}
              </p>
              <p v-if="error" class="rounded-lg bg-chart-rose-soft px-3 py-2 text-sm font-medium text-chart-rose">
                {{ error }}
              </p>
            </div>

            <div class="flex items-start gap-3 rounded-lg border border-border bg-surface-muted px-3 py-3 text-sm text-muted-foreground">
              <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
              <p>
                Acesso restrito a usuários cadastrados pela administração da clínica.
              </p>
            </div>

            <button
              class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground transition hover:bg-brand-strong disabled:cursor-not-allowed disabled:opacity-70"
              type="submit"
              :disabled="auth.loading"
            >
              <Loader2 v-if="auth.loading" class="h-4 w-4 animate-spin" />
              <span>{{ auth.loading ? 'Entrando...' : 'Entrar' }}</span>
              <ArrowRight v-if="!auth.loading" class="h-4 w-4" />
            </button>

            <p class="text-center text-sm text-muted-foreground">
              Precisa de acesso? Solicite o cadastro ao administrador da sua clínica.
            </p>
          </form>
        </div>
      </div>

      <footer class="mx-auto mt-8 text-center text-xs text-muted-foreground">
        © 2026 Clinic SaaS · Termos · Privacidade
      </footer>
    </section>
  </main>
</template>
