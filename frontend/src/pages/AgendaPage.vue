<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { CalendarPlus, Pencil, Search, Trash2 } from 'lucide-vue-next'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppTable from '@/components/ui/AppTable.vue'
import {
  atualizarAgendamento,
  criarAgendamento,
  listarAgendamentos,
  removerAgendamento,
  type Appointment,
  type AppointmentPayload,
  type AppointmentStatus,
} from '@/services/appointments'
import { listarPacientes, type Patient } from '@/services/patient'

const today = new Date().toISOString().slice(0, 10)
const appointments = ref<Appointment[]>([])
const patients = ref<Patient[]>([])
const search = ref('')
const date = ref(today)
const status = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const successMessage = ref('')
const modalOpen = ref(false)
const editingId = ref<number | null>(null)

const emptyForm: AppointmentPayload = {
  patient_id: null,
  title: '',
  professional: '',
  type: 'Consulta',
  starts_at: `${today}T08:00`,
  ends_at: `${today}T08:30`,
  status: 'scheduled',
  price: 0,
  notes: '',
}

const form = reactive<AppointmentPayload>({ ...emptyForm })

const statusLabels: Record<AppointmentStatus, string> = {
  scheduled: 'Agendado',
  confirmed: 'Confirmado',
  completed: 'Concluído',
  canceled: 'Cancelado',
  no_show: 'Faltou',
}

const totals = computed(() => ({
  total: appointments.value.length,
  confirmed: appointments.value.filter((item) => item.status === 'confirmed').length,
  completed: appointments.value.filter((item) => item.status === 'completed').length,
  pending: appointments.value.filter((item) => ['scheduled', 'confirmed'].includes(item.status)).length,
}))

function formatDateTime(value: string) {
  return new Date(value.replace(' ', 'T')).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function statusTone(value: AppointmentStatus) {
  if (value === 'completed' || value === 'confirmed') return 'success'
  if (value === 'canceled' || value === 'no_show') return 'danger'
  return 'neutral'
}

function resetForm() {
  Object.assign(form, { ...emptyForm, starts_at: `${date.value || today}T08:00`, ends_at: `${date.value || today}T08:30` })
}

function openCreateModal() {
  editingId.value = null
  resetForm()
  modalOpen.value = true
}

function openEditModal(appointment: Appointment) {
  editingId.value = appointment.id
  Object.assign(form, {
    patient_id: appointment.patient_id ?? null,
    title: appointment.title,
    professional: appointment.professional ?? '',
    type: appointment.type,
    starts_at: appointment.starts_at.replace(' ', 'T').slice(0, 16),
    ends_at: appointment.ends_at ? appointment.ends_at.replace(' ', 'T').slice(0, 16) : null,
    status: appointment.status,
    price: Number(appointment.price ?? 0),
    notes: appointment.notes ?? '',
  })
  modalOpen.value = true
}

function closeModal() {
  modalOpen.value = false
  editingId.value = null
  resetForm()
}

async function loadPatients() {
  const response = await listarPacientes('')
  patients.value = response.data
}

async function loadAppointments() {
  loading.value = true
  error.value = ''

  try {
    const response = await listarAgendamentos({
      search: search.value,
      date: date.value,
      status: status.value,
    })
    appointments.value = response.data
  } catch {
    error.value = 'Não foi possível carregar a agenda.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  saving.value = true
  error.value = ''
  successMessage.value = ''

  try {
    const payload = {
      ...form,
      patient_id: form.patient_id || null,
      ends_at: form.ends_at || null,
      price: Number(form.price || 0),
    }

    if (editingId.value) {
      await atualizarAgendamento(editingId.value, payload)
      successMessage.value = 'Agendamento atualizado com sucesso.'
    } else {
      await criarAgendamento(payload)
      successMessage.value = 'Agendamento criado com sucesso.'
    }

    closeModal()
    await loadAppointments()
  } catch {
    error.value = 'Não foi possível salvar o agendamento. Confira os campos.'
  } finally {
    saving.value = false
  }
}

async function removeAppointment(appointment: Appointment) {
  if (!confirm(`Remover ${appointment.title}?`)) return

  await removerAgendamento(appointment.id)
  successMessage.value = 'Agendamento removido com sucesso.'
  await loadAppointments()
}

onMounted(async () => {
  await Promise.all([loadPatients(), loadAppointments()])
})
</script>

<template>
  <AppLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#0F172A]">Agenda</h1>
        <p class="mt-1 text-sm text-slate-500">Agendamentos, retornos, exames e teleconsultas</p>
      </div>
      <AppButton @click="openCreateModal">
        <CalendarPlus class="h-4 w-4" />
        Novo agendamento
      </AppButton>
    </div>

    <section class="mb-4 grid gap-3 md:grid-cols-4">
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Total no filtro</p>
        <strong class="mt-2 block text-2xl">{{ totals.total }}</strong>
      </AppCard>
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Confirmados</p>
        <strong class="mt-2 block text-2xl">{{ totals.confirmed }}</strong>
      </AppCard>
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Concluídos</p>
        <strong class="mt-2 block text-2xl">{{ totals.completed }}</strong>
      </AppCard>
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Pendentes</p>
        <strong class="mt-2 block text-2xl">{{ totals.pending }}</strong>
      </AppCard>
    </section>

    <section class="mb-4 grid gap-3 rounded-lg border border-[#E2E8F0] bg-white p-4 lg:grid-cols-[1fr_180px_180px_auto]">
      <label class="relative">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          v-model="search"
          class="h-10 w-full rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] pl-10 pr-3 text-sm outline-none focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20"
          placeholder="Buscar por paciente, profissional ou título"
          type="search"
          @keyup.enter="loadAppointments"
        />
      </label>
      <input v-model="date" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm" type="date" />
      <select v-model="status" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm">
        <option value="">Todos os status</option>
        <option value="scheduled">Agendado</option>
        <option value="confirmed">Confirmado</option>
        <option value="completed">Concluído</option>
        <option value="canceled">Cancelado</option>
        <option value="no_show">Faltou</option>
      </select>
      <AppButton variant="secondary" @click="loadAppointments">Filtrar</AppButton>
    </section>

    <p v-if="error" class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</p>
    <p v-if="successMessage" class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</p>

    <div v-if="loading" class="rounded-lg border border-[#E2E8F0] bg-white p-8 text-center text-sm text-slate-500">
      Carregando agenda...
    </div>

    <AppTable v-else>
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 font-semibold text-slate-600">Horário</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Paciente</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Atendimento</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Profissional</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Status</th>
          <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-[#E2E8F0]">
        <tr v-if="appointments.length === 0">
          <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum agendamento encontrado.</td>
        </tr>
        <tr v-for="appointment in appointments" :key="appointment.id" class="hover:bg-slate-50">
          <td class="px-4 py-3 font-semibold">{{ formatDateTime(appointment.starts_at) }}</td>
          <td class="px-4 py-3">
            <strong>{{ appointment.patient?.nome ?? 'Paciente avulso' }}</strong>
            <p class="text-xs text-slate-500">{{ appointment.patient?.telefone ?? 'Sem contato' }}</p>
          </td>
          <td class="px-4 py-3">
            <strong>{{ appointment.title }}</strong>
            <p class="text-xs text-slate-500">{{ appointment.type }}</p>
          </td>
          <td class="px-4 py-3 text-slate-600">{{ appointment.professional || '-' }}</td>
          <td class="px-4 py-3">
            <AppBadge :tone="statusTone(appointment.status)">{{ statusLabels[appointment.status] }}</AppBadge>
          </td>
          <td class="px-4 py-3 text-right">
            <button class="mr-1 inline-grid h-9 w-9 place-items-center rounded-lg text-slate-600 hover:bg-slate-100" title="Editar" @click="openEditModal(appointment)">
              <Pencil class="h-4 w-4" />
            </button>
            <button class="inline-grid h-9 w-9 place-items-center rounded-lg text-rose-600 hover:bg-rose-50" title="Remover" @click="removeAppointment(appointment)">
              <Trash2 class="h-4 w-4" />
            </button>
          </td>
        </tr>
      </tbody>
    </AppTable>

    <AppModal :open="modalOpen" :title="editingId ? 'Editar agendamento' : 'Novo agendamento'" @close="closeModal">
      <form class="grid gap-4" @submit.prevent="submit">
        <div class="grid gap-4 md:grid-cols-2">
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Paciente
            <select v-model.number="form.patient_id" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm">
              <option :value="null">Paciente avulso</option>
              <option v-for="patient in patients" :key="patient.id" :value="patient.id">{{ patient.nome }}</option>
            </select>
          </label>
          <AppInput v-model="form.title" label="Título" required />
          <AppInput v-model="form.professional" label="Profissional" />
          <AppInput v-model="form.type" label="Tipo" required />
          <AppInput v-model="form.starts_at" label="Início" type="datetime-local" required />
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Fim
            <input v-model="form.ends_at" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm" type="datetime-local" />
          </label>
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Status
            <select v-model="form.status" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm">
              <option value="scheduled">Agendado</option>
              <option value="confirmed">Confirmado</option>
              <option value="completed">Concluído</option>
              <option value="canceled">Cancelado</option>
              <option value="no_show">Faltou</option>
            </select>
          </label>
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Valor
            <input v-model.number="form.price" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm" min="0" step="0.01" type="number" />
          </label>
        </div>

        <label class="grid gap-1.5 text-sm font-medium text-slate-700">
          Observações
          <textarea v-model="form.notes" class="min-h-24 rounded-lg border border-[#E2E8F0] px-3 py-2 text-sm outline-none focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20" />
        </label>

        <div class="flex justify-end gap-2 border-t border-[#E2E8F0] pt-4">
          <AppButton variant="secondary" @click="closeModal">Cancelar</AppButton>
          <AppButton type="submit" :disabled="saving">{{ saving ? 'Salvando...' : 'Salvar' }}</AppButton>
        </div>
      </form>
    </AppModal>
  </AppLayout>
</template>
