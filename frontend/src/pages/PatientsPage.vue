<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Eye, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppTable from '@/components/ui/AppTable.vue'
import { estadosBrasil } from '@/constants/estados'
import {
  atualizarPaciente,
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
const editingPatientId = ref<number | null>(null)
const selectedPatient = ref<Patient | null>(null)
const detailsModalOpen = ref(false)
const patientPendingDeletion = ref<Patient | null>(null)
const deleteModalOpen = ref(false)
const deleting = ref(false)
const successMessage = ref('')

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

function normalizePatientForm(patient: Patient): PatientPayload {
  return {
    nome: patient.nome,
    cpf: patient.cpf,
    telefone: patient.telefone ?? '',
    email: patient.email ?? '',
    data_nascimento: patient.data_nascimento ?? '',
    convenio: patient.convenio ?? '',
    cep: patient.cep ?? '',
    endereco: patient.endereco ?? '',
    numero: patient.numero ?? '',
    bairro: patient.bairro ?? '',
    cidade: patient.cidade ?? '',
    estado: patient.estado ?? '',
    observacoes: patient.observacoes ?? '',
    ativo: patient.ativo,
  }
}

function resetForm() {
  Object.assign(form, emptyForm)
}

function openCreateModal() {
  editingPatientId.value = null
  resetForm()
  modalOpen.value = true
}

function openEditModal(patient: Patient) {
  editingPatientId.value = patient.id
  Object.assign(form, normalizePatientForm(patient))
  modalOpen.value = true
}

function openDetailsModal(patient: Patient) {
  selectedPatient.value = patient
  detailsModalOpen.value = true
}

function closeDetailsModal() {
  detailsModalOpen.value = false
  selectedPatient.value = null
}

function editSelectedPatient() {
  if (!selectedPatient.value) return

  const patient = selectedPatient.value
  closeDetailsModal()
  openEditModal(patient)
}

function closeModal() {
  modalOpen.value = false
  editingPatientId.value = null
  resetForm()
}

function openDeleteModal(patient: Patient) {
  patientPendingDeletion.value = patient
  deleteModalOpen.value = true
}

function closeDeleteModal() {
  deleteModalOpen.value = false
  patientPendingDeletion.value = null
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
  successMessage.value = ''

  try {
    const payload = {
      ...form,
      estado: form.estado?.toUpperCase(),
    }
    const wasEditing = Boolean(editingPatientId.value)

    if (editingPatientId.value) {
      await atualizarPaciente(editingPatientId.value, payload)
    } else {
      await criarPaciente(payload)
    }

    closeModal()
    await loadPatients()
    successMessage.value = wasEditing ? 'Paciente atualizado com sucesso.' : 'Paciente cadastrado com sucesso.'
  } catch {
    error.value = editingPatientId.value
      ? 'Não foi possível atualizar o paciente. Confira os campos obrigatórios.'
      : 'Não foi possível salvar o paciente. Confira os campos obrigatórios.'
  } finally {
    saving.value = false
  }
}

async function confirmDeletePatient() {
  if (!patientPendingDeletion.value) return

  deleting.value = true
  error.value = ''
  successMessage.value = ''
  try {
    await removerPaciente(patientPendingDeletion.value.id)
    closeDeleteModal()
    await loadPatients()
    successMessage.value = 'Paciente removido com sucesso.'
  } catch {
    error.value = 'Não foi possível remover o paciente.'
  } finally {
    deleting.value = false
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
      <AppButton @click="openCreateModal">
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
    <p v-if="successMessage" class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ successMessage }}
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
              class="mr-1 inline-grid h-9 w-9 place-items-center rounded-lg text-slate-600 hover:bg-slate-100"
              title="Ver detalhes"
              @click="openDetailsModal(patient)"
            >
              <Eye class="h-4 w-4" />
            </button>
            <button
              class="mr-1 inline-grid h-9 w-9 place-items-center rounded-lg text-slate-600 hover:bg-slate-100"
              title="Editar paciente"
              @click="openEditModal(patient)"
            >
              <Pencil class="h-4 w-4" />
            </button>
            <button
              class="inline-grid h-9 w-9 place-items-center rounded-lg text-rose-600 hover:bg-rose-50"
              title="Remover paciente"
              @click="openDeleteModal(patient)"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </td>
        </tr>
      </tbody>
    </AppTable>

    <AppModal
      :open="modalOpen"
      :title="editingPatientId ? 'Editar paciente' : 'Novo paciente'"
      @close="closeModal"
    >
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
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Estado
            <select
              v-model="form.estado"
              class="h-10 rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#0F172A] outline-none transition focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20"
            >
              <option value="">Selecione um estado</option>
              <option v-for="estado in estadosBrasil" :key="estado.sigla" :value="estado.sigla">
                {{ estado.sigla }} - {{ estado.nome }}
              </option>
            </select>
          </label>
        </div>

        <label class="grid gap-1.5 text-sm font-medium text-slate-700">
          Observações
          <textarea
            v-model="form.observacoes"
            class="min-h-28 rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-sm outline-none focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20"
          />
        </label>

        <div class="flex justify-end gap-2 border-t border-[#E2E8F0] pt-4">
          <AppButton variant="secondary" @click="closeModal">Cancelar</AppButton>
          <AppButton type="submit" :disabled="saving">
            {{ saving ? 'Salvando...' : editingPatientId ? 'Atualizar paciente' : 'Salvar paciente' }}
          </AppButton>
        </div>
      </form>
    </AppModal>

    <AppModal :open="detailsModalOpen" title="Detalhes do paciente" @close="closeDetailsModal">
      <section v-if="selectedPatient" class="grid gap-5">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-[#E2E8F0] pb-4">
          <div>
            <h2 class="text-xl font-bold text-[#0F172A]">{{ selectedPatient.nome }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ selectedPatient.email || 'Sem e-mail cadastrado' }}</p>
          </div>
          <AppBadge :tone="selectedPatient.ativo ? 'success' : 'danger'">
            {{ selectedPatient.ativo ? 'Ativo' : 'Inativo' }}
          </AppBadge>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="rounded-lg bg-slate-50 p-4">
            <span class="text-xs font-semibold uppercase text-slate-500">CPF</span>
            <p class="mt-1 text-sm font-medium text-[#0F172A]">{{ selectedPatient.cpf }}</p>
          </div>
          <div class="rounded-lg bg-slate-50 p-4">
            <span class="text-xs font-semibold uppercase text-slate-500">Telefone</span>
            <p class="mt-1 text-sm font-medium text-[#0F172A]">{{ selectedPatient.telefone || '-' }}</p>
          </div>
          <div class="rounded-lg bg-slate-50 p-4">
            <span class="text-xs font-semibold uppercase text-slate-500">Data de nascimento</span>
            <p class="mt-1 text-sm font-medium text-[#0F172A]">{{ selectedPatient.data_nascimento || '-' }}</p>
          </div>
          <div class="rounded-lg bg-slate-50 p-4">
            <span class="text-xs font-semibold uppercase text-slate-500">Convênio</span>
            <p class="mt-1 text-sm font-medium text-[#0F172A]">{{ selectedPatient.convenio || 'Particular' }}</p>
          </div>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-[#0F172A]">Endereço</h3>
          <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-slate-50 p-4">
              <span class="text-xs font-semibold uppercase text-slate-500">CEP</span>
              <p class="mt-1 text-sm font-medium text-[#0F172A]">{{ selectedPatient.cep || '-' }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 md:col-span-2">
              <span class="text-xs font-semibold uppercase text-slate-500">Logradouro</span>
              <p class="mt-1 text-sm font-medium text-[#0F172A]">
                {{ selectedPatient.endereco || '-' }}
                <span v-if="selectedPatient.numero">, {{ selectedPatient.numero }}</span>
              </p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
              <span class="text-xs font-semibold uppercase text-slate-500">Bairro</span>
              <p class="mt-1 text-sm font-medium text-[#0F172A]">{{ selectedPatient.bairro || '-' }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
              <span class="text-xs font-semibold uppercase text-slate-500">Cidade</span>
              <p class="mt-1 text-sm font-medium text-[#0F172A]">{{ selectedPatient.cidade || '-' }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
              <span class="text-xs font-semibold uppercase text-slate-500">Estado</span>
              <p class="mt-1 text-sm font-medium text-[#0F172A]">{{ selectedPatient.estado || '-' }}</p>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-slate-50 p-4">
          <span class="text-xs font-semibold uppercase text-slate-500">Observações</span>
          <p class="mt-2 whitespace-pre-line text-sm text-[#0F172A]">
            {{ selectedPatient.observacoes || 'Nenhuma observação registrada.' }}
          </p>
        </div>

        <div class="flex justify-end gap-2 border-t border-[#E2E8F0] pt-4">
          <AppButton variant="secondary" @click="closeDetailsModal">Fechar</AppButton>
          <AppButton @click="editSelectedPatient">
            Editar paciente
          </AppButton>
        </div>
      </section>
    </AppModal>

    <AppModal :open="deleteModalOpen" title="Remover paciente" @close="closeDeleteModal">
      <section v-if="patientPendingDeletion" class="grid gap-5">
        <div class="rounded-lg bg-rose-50 p-4 text-sm text-rose-800">
          Esta ação removerá o paciente da listagem, mas manterá o registro no histórico do sistema.
        </div>

        <div>
          <p class="text-sm text-slate-600">Paciente selecionado</p>
          <strong class="mt-1 block text-lg text-[#0F172A]">{{ patientPendingDeletion.nome }}</strong>
          <span class="text-sm text-slate-500">CPF {{ patientPendingDeletion.cpf }}</span>
        </div>

        <div class="flex justify-end gap-2 border-t border-[#E2E8F0] pt-4">
          <AppButton variant="secondary" :disabled="deleting" @click="closeDeleteModal">Cancelar</AppButton>
          <AppButton variant="danger" :disabled="deleting" @click="confirmDeletePatient">
            {{ deleting ? 'Removendo...' : 'Remover paciente' }}
          </AppButton>
        </div>
      </section>
    </AppModal>
  </AppLayout>
</template>
