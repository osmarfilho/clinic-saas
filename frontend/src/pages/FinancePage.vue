<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { isAxiosError } from 'axios'
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppTable from '@/components/ui/AppTable.vue'
import {
  atualizarLancamento,
  criarLancamento,
  listarLancamentos,
  removerLancamento,
  type FinancialTransaction,
  type FinancialTransactionPayload,
  type FinancialSummary,
  type TransactionStatus,
} from '@/services/finance'
import { listarPacientes, type Patient } from '@/services/patient'

const today = new Date().toISOString().slice(0, 10)
const transactions = ref<FinancialTransaction[]>([])
const summary = ref<FinancialSummary>({
  paid_income: 0,
  paid_expenses: 0,
  pending_income: 0,
  pending_expenses: 0,
  current_balance: 0,
  forecast_balance: 0,
})
const patients = ref<Patient[]>([])
const search = ref('')
const typeFilter = ref('')
const statusFilter = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const successMessage = ref('')
const modalOpen = ref(false)
const editingId = ref<number | null>(null)
const transactionPendingDeletion = ref<FinancialTransaction | null>(null)
const deleteModalOpen = ref(false)
const deleting = ref(false)
const formErrors = reactive<Partial<Record<keyof FinancialTransactionPayload, string>>>({})

const moneyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

const emptyForm: FinancialTransactionPayload = {
  patient_id: null,
  description: '',
  type: 'income',
  category: '',
  amount: 0,
  due_date: today,
  paid_at: '',
  status: 'pending',
  payment_method: '',
  notes: '',
}

const form = reactive<FinancialTransactionPayload>({ ...emptyForm })
const canSubmit = computed(() => Object.keys(validateForm()).length === 0 && !saving.value)

const statusLabels: Record<TransactionStatus, string> = {
  pending: 'Pendente',
  paid: 'Pago',
  canceled: 'Cancelado',
}

function statusTone(value: TransactionStatus) {
  if (value === 'paid') return 'success'
  if (value === 'canceled') return 'danger'
  return 'neutral'
}

function clearFormErrors() {
  Object.keys(formErrors).forEach((key) => {
    delete formErrors[key as keyof FinancialTransactionPayload]
  })
}

function validateForm() {
  const errors: Partial<Record<keyof FinancialTransactionPayload, string>> = {}

  if (!form.description.trim()) {
    errors.description = 'Informe a descrição do lançamento.'
  }

  if (!form.type) {
    errors.type = 'Informe se é receita ou despesa.'
  }

  if (!form.status) {
    errors.status = 'Informe o status do lançamento.'
  }

  if (!form.due_date) {
    errors.due_date = 'Informe a data de vencimento.'
  }

  if (!Number.isFinite(Number(form.amount)) || Number(form.amount) <= 0) {
    errors.amount = 'Informe um valor maior que zero.'
  }

  if (form.type === 'income' && !form.patient_id) {
    errors.patient_id = 'Receitas devem estar vinculadas a um paciente.'
  }

  return errors
}

function applyFormErrors(errors: Partial<Record<keyof FinancialTransactionPayload, string>>) {
  clearFormErrors()
  Object.assign(formErrors, errors)
}

function errorMessageFromResponse(error: unknown, fallback: string) {
  if (!isAxiosError(error)) return fallback

  const errors = error.response?.data?.errors
  if (errors && typeof errors === 'object') {
    const firstError = Object.values(errors).flat().find(Boolean)
    if (typeof firstError === 'string') return firstError
  }

  return error.response?.data?.message ?? fallback
}

function resetForm() {
  Object.assign(form, emptyForm)
  clearFormErrors()
}

function openCreateModal() {
  editingId.value = null
  resetForm()
  modalOpen.value = true
}

function openEditModal(transaction: FinancialTransaction) {
  editingId.value = transaction.id
  Object.assign(form, {
    patient_id: transaction.patient_id ?? null,
    description: transaction.description,
    type: transaction.type,
    category: transaction.category ?? '',
    amount: Number(transaction.amount),
    due_date: transaction.due_date,
    paid_at: transaction.paid_at ?? null,
    status: transaction.status,
    payment_method: transaction.payment_method ?? '',
    notes: transaction.notes ?? '',
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

async function loadTransactions() {
  loading.value = true
  error.value = ''

  try {
    const response = await listarLancamentos({
      search: search.value,
      type: typeFilter.value,
      status: statusFilter.value,
    })
    transactions.value = response.data
    summary.value = response.summary
  } catch {
    error.value = 'Não foi possível carregar o financeiro.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  const validationErrors = validateForm()
  if (Object.keys(validationErrors).length > 0) {
    applyFormErrors(validationErrors)
    error.value = 'Confira os campos destacados antes de salvar.'
    return
  }

  saving.value = true
  error.value = ''
  successMessage.value = ''
  clearFormErrors()

  try {
    const payload = {
      ...form,
      patient_id: form.patient_id || null,
      paid_at: form.status === 'paid' ? (form.paid_at || today) : null,
      amount: Number(form.amount || 0),
    }

    if (editingId.value) {
      await atualizarLancamento(editingId.value, payload)
      successMessage.value = 'Lançamento atualizado com sucesso.'
    } else {
      await criarLancamento(payload)
      successMessage.value = 'Lançamento criado com sucesso.'
    }

    closeModal()
    await loadTransactions()
  } catch (requestError) {
    error.value = errorMessageFromResponse(requestError, 'Não foi possível salvar o lançamento. Confira os campos.')
  } finally {
    saving.value = false
  }
}

function openDeleteModal(transaction: FinancialTransaction) {
  transactionPendingDeletion.value = transaction
  deleteModalOpen.value = true
}

function closeDeleteModal() {
  deleteModalOpen.value = false
  transactionPendingDeletion.value = null
}

async function confirmDeleteTransaction() {
  if (!transactionPendingDeletion.value) return

  deleting.value = true
  error.value = ''
  successMessage.value = ''

  try {
    await removerLancamento(transactionPendingDeletion.value.id)
    closeDeleteModal()
    successMessage.value = 'Lançamento removido com sucesso.'
    await loadTransactions()
  } catch {
    error.value = 'Não foi possível remover o lançamento.'
  } finally {
    deleting.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadPatients(), loadTransactions()])
})
</script>

<template>
  <AppLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#0F172A]">Financeiro</h1>
        <p class="mt-1 text-sm text-slate-500">Receitas, despesas, pendências e fluxo da clínica</p>
      </div>
      <AppButton @click="openCreateModal">
        <Plus class="h-4 w-4" />
        Novo lançamento
      </AppButton>
    </div>

    <section class="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Receitas pagas</p>
        <strong class="mt-2 block text-xl">{{ moneyFormatter.format(summary.paid_income) }}</strong>
      </AppCard>
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Despesas pagas</p>
        <strong class="mt-2 block text-xl">{{ moneyFormatter.format(summary.paid_expenses) }}</strong>
      </AppCard>
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Receitas pendentes</p>
        <strong class="mt-2 block text-xl">{{ moneyFormatter.format(summary.pending_income) }}</strong>
      </AppCard>
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Despesas pendentes</p>
        <strong class="mt-2 block text-xl">{{ moneyFormatter.format(summary.pending_expenses) }}</strong>
      </AppCard>
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Saldo atual</p>
        <strong class="mt-2 block text-xl">{{ moneyFormatter.format(summary.current_balance) }}</strong>
      </AppCard>
      <AppCard class="p-4">
        <p class="text-sm text-slate-500">Saldo previsto</p>
        <strong class="mt-2 block text-xl">{{ moneyFormatter.format(summary.forecast_balance) }}</strong>
      </AppCard>
    </section>

    <section class="mb-4 grid gap-3 rounded-lg border border-[#E2E8F0] bg-white p-4 lg:grid-cols-[1fr_180px_180px_auto]">
      <label class="relative">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input v-model="search" class="h-10 w-full rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] pl-10 pr-3 text-sm outline-none focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20" placeholder="Buscar lançamento ou paciente" type="search" @keyup.enter="loadTransactions" />
      </label>
      <select v-model="typeFilter" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm">
        <option value="">Todos os tipos</option>
        <option value="income">Receitas</option>
        <option value="expense">Despesas</option>
      </select>
      <select v-model="statusFilter" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm">
        <option value="">Todos os status</option>
        <option value="pending">Pendente</option>
        <option value="paid">Pago</option>
        <option value="canceled">Cancelado</option>
      </select>
      <AppButton variant="secondary" @click="loadTransactions">Filtrar</AppButton>
    </section>

    <p v-if="error" class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</p>
    <p v-if="successMessage" class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</p>

    <div v-if="loading" class="rounded-lg border border-[#E2E8F0] bg-white p-8 text-center text-sm text-slate-500">
      Carregando financeiro...
    </div>

    <AppTable v-else>
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 font-semibold text-slate-600">Descrição</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Paciente</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Tipo</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Valor</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Vencimento</th>
          <th class="px-4 py-3 font-semibold text-slate-600">Status</th>
          <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-[#E2E8F0]">
        <tr v-if="transactions.length === 0">
          <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum lançamento encontrado.</td>
        </tr>
        <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-slate-50">
          <td class="px-4 py-3">
            <strong>{{ transaction.description }}</strong>
            <p class="text-xs text-slate-500">{{ transaction.category || 'Sem categoria' }}</p>
          </td>
          <td class="px-4 py-3 text-slate-600">{{ transaction.patient?.nome ?? '-' }}</td>
          <td class="px-4 py-3">{{ transaction.type === 'income' ? 'Receita' : 'Despesa' }}</td>
          <td class="px-4 py-3 font-semibold">{{ moneyFormatter.format(Number(transaction.amount)) }}</td>
          <td class="px-4 py-3 text-slate-600">{{ transaction.due_date }}</td>
          <td class="px-4 py-3"><AppBadge :tone="statusTone(transaction.status)">{{ statusLabels[transaction.status] }}</AppBadge></td>
          <td class="px-4 py-3 text-right">
            <button class="mr-1 inline-grid h-9 w-9 place-items-center rounded-lg text-slate-600 hover:bg-slate-100" title="Editar" @click="openEditModal(transaction)">
              <Pencil class="h-4 w-4" />
            </button>
            <button class="inline-grid h-9 w-9 place-items-center rounded-lg text-rose-600 hover:bg-rose-50" title="Remover" @click="openDeleteModal(transaction)">
              <Trash2 class="h-4 w-4" />
            </button>
          </td>
        </tr>
      </tbody>
    </AppTable>

    <AppModal :open="modalOpen" :title="editingId ? 'Editar lançamento' : 'Novo lançamento'" @close="closeModal">
      <form class="grid gap-4" @submit.prevent="submit">
        <div class="grid gap-4 md:grid-cols-2">
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Paciente
            <select
              v-model.number="form.patient_id"
              class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm"
              :class="{ 'border-rose-300': formErrors.patient_id }"
              :aria-invalid="Boolean(formErrors.patient_id)"
            >
              <option :value="null">Sem paciente</option>
              <option v-for="patient in patients" :key="patient.id" :value="patient.id">{{ patient.nome }}</option>
            </select>
            <span v-if="formErrors.patient_id" class="text-xs font-medium text-rose-600">{{ formErrors.patient_id }}</span>
          </label>
          <AppInput v-model="form.description" label="Descrição" :error="formErrors.description" required />
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Tipo
            <select v-model="form.type" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm" :aria-invalid="Boolean(formErrors.type)">
              <option value="income">Receita</option>
              <option value="expense">Despesa</option>
            </select>
            <span v-if="formErrors.type" class="text-xs font-medium text-rose-600">{{ formErrors.type }}</span>
          </label>
          <AppInput v-model="form.category" label="Categoria" />
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Valor
            <input
              v-model.number="form.amount"
              class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm outline-none transition focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20"
              :class="{ 'border-rose-300 focus:border-rose-400 focus:ring-rose-100': formErrors.amount }"
              min="0.01"
              step="0.01"
              type="number"
              inputmode="decimal"
              :aria-invalid="Boolean(formErrors.amount)"
            />
            <span v-if="formErrors.amount" class="text-xs font-medium text-rose-600">{{ formErrors.amount }}</span>
          </label>
          <AppInput v-model="form.due_date" label="Vencimento" type="date" :error="formErrors.due_date" required />
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Status
            <select v-model="form.status" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm" :aria-invalid="Boolean(formErrors.status)">
              <option value="pending">Pendente</option>
              <option value="paid">Pago</option>
              <option value="canceled">Cancelado</option>
            </select>
            <span v-if="formErrors.status" class="text-xs font-medium text-rose-600">{{ formErrors.status }}</span>
          </label>
          <label class="grid gap-1.5 text-sm font-medium text-slate-700">
            Pago em
            <input v-model="form.paid_at" class="h-10 rounded-lg border border-[#E2E8F0] px-3 text-sm" type="date" />
          </label>
          <AppInput v-model="form.payment_method" label="Forma de pagamento" />
        </div>

        <label class="grid gap-1.5 text-sm font-medium text-slate-700">
          Observações
          <textarea v-model="form.notes" class="min-h-24 rounded-lg border border-[#E2E8F0] px-3 py-2 text-sm outline-none focus:border-[#6FF6A5] focus:ring-4 focus:ring-[#6FF6A5]/20" />
        </label>

        <div class="flex justify-end gap-2 border-t border-[#E2E8F0] pt-4">
          <AppButton variant="secondary" @click="closeModal">Cancelar</AppButton>
          <AppButton type="submit" :disabled="!canSubmit">{{ saving ? 'Salvando...' : 'Salvar' }}</AppButton>
        </div>
      </form>
    </AppModal>

    <AppModal :open="deleteModalOpen" title="Remover lançamento" @close="closeDeleteModal">
      <section v-if="transactionPendingDeletion" class="grid gap-5">
        <div class="rounded-lg bg-rose-50 p-4 text-sm text-rose-800">
          Esta ação remove o lançamento da listagem financeira e atualiza os totalizadores.
        </div>

        <div>
          <p class="text-sm text-slate-600">Lançamento selecionado</p>
          <strong class="mt-1 block text-lg text-[#0F172A]">{{ transactionPendingDeletion.description }}</strong>
          <span class="text-sm text-slate-500">{{ moneyFormatter.format(Number(transactionPendingDeletion.amount)) }}</span>
        </div>

        <div class="flex justify-end gap-2 border-t border-[#E2E8F0] pt-4">
          <AppButton variant="secondary" :disabled="deleting" @click="closeDeleteModal">Cancelar</AppButton>
          <AppButton variant="danger" :disabled="deleting" @click="confirmDeleteTransaction">
            {{ deleting ? 'Removendo...' : 'Remover lançamento' }}
          </AppButton>
        </div>
      </section>
    </AppModal>
  </AppLayout>
</template>
