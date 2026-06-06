<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Save } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import { carregarConfiguracoes, salvarConfiguracoes, type ClinicSettings } from '@/services/settings'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const successMessage = ref('')

const form = reactive<ClinicSettings>({
  clinic_name: '',
  document: '',
  phone: '',
  email: '',
  address: '',
  opening_time: '08:00',
  closing_time: '18:00',
  appointment_duration: '30',
  daily_capacity: '32',
  average_wait_minutes: '14',
  satisfaction_rate: '94',
})

async function loadSettings() {
  loading.value = true
  error.value = ''

  try {
    Object.assign(form, await carregarConfiguracoes())
  } catch {
    error.value = 'Não foi possível carregar as configurações.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  saving.value = true
  error.value = ''
  successMessage.value = ''

  try {
    Object.assign(form, await salvarConfiguracoes(form))
    successMessage.value = 'Configurações salvas com sucesso.'
  } catch {
    error.value = 'Não foi possível salvar as configurações. Confira os campos.'
  } finally {
    saving.value = false
  }
}

onMounted(loadSettings)
</script>

<template>
  <AppLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#0F172A]">Configurações</h1>
        <p class="mt-1 text-sm text-slate-500">Dados da clínica, agenda e indicadores operacionais</p>
      </div>
    </div>

    <p v-if="error" class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</p>
    <p v-if="successMessage" class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</p>

    <div v-if="loading" class="rounded-lg border border-[#E2E8F0] bg-white p-8 text-center text-sm text-slate-500">
      Carregando configurações...
    </div>

    <form v-else class="grid gap-4" @submit.prevent="submit">
      <AppCard class="p-5">
        <h2 class="mb-4 text-lg font-semibold">Dados da clínica</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <AppInput v-model="form.clinic_name" label="Nome da clínica" required />
          <AppInput v-model="form.document" label="CNPJ/Documento" />
          <AppInput v-model="form.phone" label="Telefone" />
          <AppInput v-model="form.email" label="E-mail" type="email" />
          <div class="md:col-span-2">
            <AppInput v-model="form.address" label="Endereço" />
          </div>
        </div>
      </AppCard>

      <AppCard class="p-5">
        <h2 class="mb-4 text-lg font-semibold">Agenda e operação</h2>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <AppInput v-model="form.opening_time" label="Abertura" type="time" required />
          <AppInput v-model="form.closing_time" label="Fechamento" type="time" required />
          <AppInput v-model="form.appointment_duration" label="Duração padrão (min)" type="number" required />
          <AppInput v-model="form.daily_capacity" label="Capacidade diária" type="number" required />
          <AppInput v-model="form.average_wait_minutes" label="Espera média (min)" type="number" required />
          <AppInput v-model="form.satisfaction_rate" label="Satisfação (%)" type="number" required />
        </div>
      </AppCard>

      <div class="flex justify-end">
        <AppButton type="submit" :disabled="saving">
          <Save class="h-4 w-4" />
          {{ saving ? 'Salvando...' : 'Salvar configurações' }}
        </AppButton>
      </div>
    </form>
  </AppLayout>
</template>
