<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Plus, Search, Trash2 } from 'lucide-vue-next'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppTable from '@/components/ui/AppTable.vue'
import {
  criarPaciente,
  listarPacientes,
  removerPaciente,
  type Patient,
  type PatientPayload,
} from '@/services/patient'

const patients = ref<Patient[]>([])
const search = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const modalOpen = ref(false)

const emptyForm: PatientPayload = {
  nome: '',
  cpf: '',
  telefone: '',
  email: '',
  data_nascimento: '',
  convenio: '',
  cep: '',
  endereco: '',
  numero: '',
  bairro: '',
  cidade: '',
  estado: '',
  observacoes: '',
}

const form = reactive<PatientPayload>({ ...emptyForm })

function resetForm() {
  Object.assign(form, emptyForm)
}

async function loadPatients() {
  loading.value = true
  error.value = ''

  try {
    const response = await listarPacientes(search.value)
    patients.value = response.data
  } catch {
    error.value = 'Não foi possível carregar os pacientes.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  saving.value = true
  error.value = ''

  try {
    await criarPaciente({
      ...form,
      estado: form.estado?.toUpperCase(),
    })
    modalOpen.value = false
    resetForm()
    await loadPatients()
  } catch {
    error.value = 'Não foi possível salvar o paciente. Confira os campos obrigatórios.'
  } finally {
    saving.value = false
  }
}

async function removePatient(patient: Patient) {
  const confirmed = window.confirm(`Remover o paciente ${patient.nome}?`)

  if (!confirmed) return

  try {
    await removerPaciente(patient.id)
    await loadPatients()
  } catch {
    error.value = 'Não foi possível remover o paciente.'
  }
}

onMounted(loadPatients)
</script>

<template>
  <AppLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#0F172A]">Pacientes</h1>
        <p class="mt-1 text-sm text-slate-500">Cadastro e acompanhamento dos pacientes da clínica</p>
      </div>
      <AppButton @click="modalOpen = true">
        <Plus class="h-4 w-4" />
        Novo paciente
      </AppButton>
    </div>

    <section class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#E2E8F0] bg-white p-4">
      <label class="relative min-w-64 flex-1">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          v-model="search"
          class="h-10 w-full rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] pl-10 pr-3 text-sm outline-none focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20"
          placeholder="Buscar por nome, CPF, telefone ou e-mail"
          type="search"
          @keyup.enter="loadPatients"
        />
      </label>
      <AppButton variant="secondary" @click="loadPatients">Buscar</AppButton>
    </section>

    <p v-if="error" class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>

    <div v-if="loading" class="rounded-lg border border-[#E2E8F0] bg-white p-8 text-center text-sm text-slate-500">
      Carregando pacientes...
    </div>

    <AppTable v-else>
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 font-semibold text-slate-600">Nome</th>
          <th class="px-4 py-3 font-semibold text-slate-600">CPF</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Contato</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Convênio</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Cidade</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Status</th>
          <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-[#E2E8F0]">
        <tr v-if="patients.length === 0">
          <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
            Nenhum paciente encontrado.
          </td>
        </tr>
        <tr v-for="patient in patients" :key="patient.id" class="hover:bg-slate-50">
          <td class="px-4 py-3">
            <strong class="text-[#0F172A]">{{ patient.nome }}</strong>
            <p class="text-xs text-slate-500">{{ patient.email || 'Sem e-mail' }}</p>
          </td>
          <td class="px-4 py-3 text-slate-600">{{ patient.cpf }}</td>
          <td class="px-4 py-3 text-slate-600">{{ patient.telefone || '-' }}</td>
          <td class="px-4 py-3 text-slate-600">{{ patient.convenio || 'Particular' }}</td>
          <td class="px-4 py-3 text-slate-600">{{ patient.cidade || '-' }}</td>
          <td class="px-4 py-3">
            <AppBadge :tone="patient.ativo ? 'success' : 'danger'">
              {{ patient.ativo ? 'Ativo' : 'Inativo' }}
            </AppBadge>
          </td>
          <td class="px-4 py-3 text-right">
            <button
              class="inline-grid h-9 w-9 place-items-center rounded-lg text-rose-600 hover:bg-rose-50"
              title="Remover paciente"
              @click="removePatient(patient)"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </td>
        </tr>
      </tbody>
    </AppTable>

    <AppModal :open="modalOpen" title="Novo paciente" @close="modalOpen = false">
      <form class="grid gap-4" @submit.prevent="submit">
        <div class="grid gap-4 md:grid-cols-2">
          <AppInput v-model="form.nome" label="Nome" required />
          <AppInput v-model="form.cpf" label="CPF" required />
          <AppInput v-model="form.telefone" label="Telefone" />
          <AppInput v-model="form.email" label="E-mail" type="email" />
          <AppInput v-model="form.data_nascimento" label="Data de nascimento" type="date" />
          <AppInput v-model="form.convenio" label="Convênio" />
          <AppInput v-model="form.cep" label="CEP" />
          <AppInput v-model="form.endereco" label="Endereço" />
          <AppInput v-model="form.numero" label="Número" />
          <AppInput v-model="form.bairro" label="Bairro" />
          <AppInput v-model="form.cidade" label="Cidade" />
          <AppInput v-model="form.estado" label="Estado" placeholder="UF" />
        </div>

        <label class="grid gap-1.5 text-sm font-medium text-slate-700">
          Observações
          <textarea
            v-model="form.observacoes"
            class="min-h-28 rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-sm outline-none focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20"
          />
        </label>

        <div class="flex justify-end gap-2 border-t border-[#E2E8F0] pt-4">
          <AppButton variant="secondary" @click="modalOpen = false">Cancelar</AppButton>
          <AppButton type="submit" :disabled="saving">
            {{ saving ? 'Salvando...' : 'Salvar paciente' }}
          </AppButton>
        </div>
      </form>
    </AppModal>
  </AppLayout>
</template>
