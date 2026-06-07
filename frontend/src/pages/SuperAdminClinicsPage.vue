<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { isAxiosError } from 'axios'
import {
  Building2,
  ChevronLeft,
  ChevronRight,
  Loader2,
  Pencil,
  Plus,
  Power,
  PowerOff,
  Save,
  ShieldCheck,
} from 'lucide-vue-next'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppPhoneInput from '@/components/ui/AppPhoneInput.vue'
import AppLayout from '@/components/layout/AppLayout.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppToast from '@/components/ui/AppToast.vue'
import { formatPhone, normalizePhone, validatePhone } from '@/composables/usePhone'
import {
  ativarClinica,
  atualizarClinica,
  criarClinica,
  desativarClinica,
  listarClinicas,
  type Clinic,
  type ClinicPayload,
} from '@/services/clinics'

const clinics = ref<Clinic[]>([])
const loading = ref(false)
const saving = ref(false)
const toggling = ref(false)
const error = ref('')
const toastMessage = ref('')
const toastType = ref<'success' | 'error'>('success')
const search = ref('')
const currentPage = ref(1)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})
const modalOpen = ref(false)
const editingClinicId = ref<number | null>(null)
const toggleModalOpen = ref(false)
const pendingClinic = ref<Clinic | null>(null)
const pendingAction = ref<'activate' | 'deactivate'>('activate')
let searchTimeout: ReturnType<typeof setTimeout> | null = null
let toastTimeout: ReturnType<typeof setTimeout> | null = null

const emptyForm: ClinicPayload = {
  name: '',
  document: '',
  email: '',
  phone: '',
}

const form = reactive<ClinicPayload>({ ...emptyForm })
const formErrors = reactive<Partial<Record<keyof ClinicPayload, string>>>({})

const isEditing = computed(() => editingClinicId.value !== null)
const modalTitle = computed(() => (isEditing.value ? 'Editar clínica' : 'Nova clínica'))
const toggleTitle = computed(() =>
  pendingAction.value === 'activate' ? 'Ativar clínica' : 'Desativar clínica',
)
const toggleDescription = computed(() =>
  pendingAction.value === 'activate'
    ? 'A clínica voltará a ficar disponível para acesso administrativo.'
    : 'A clínica ficará indisponível até que seja reativada.',
)
const toggleConfirmLabel = computed(() =>
  pendingAction.value === 'activate' ? 'Ativar clínica' : 'Desativar clínica',
)
const canSubmit = computed(() => Object.keys(validateForm()).length === 0 && !saving.value)
const totalPages = computed(() => pagination.value.last_page)
const showingRange = computed(() => {
  if (pagination.value.total === 0) return '0 clínicas'

  const from = ((pagination.value.current_page - 1) * pagination.value.per_page) + 1
  const to = Math.min(from + clinics.value.length - 1, pagination.value.total)

  return `${from}-${to} de ${pagination.value.total} clínicas`
})

function clearFormErrors() {
  Object.keys(formErrors).forEach((key) => {
    delete formErrors[key as keyof ClinicPayload]
  })
}

function resetForm() {
  Object.assign(form, emptyForm)
  clearFormErrors()
}

function openCreateModal() {
  editingClinicId.value = null
  resetForm()
  error.value = ''
  modalOpen.value = true
}

function openEditModal(clinic: Clinic) {
  editingClinicId.value = clinic.id
  Object.assign(form, {
    name: clinic.name,
    document: clinic.document ?? '',
    email: clinic.email ?? '',
    phone: clinic.phone ?? '',
  })
  clearFormErrors()
  error.value = ''
  modalOpen.value = true
}

function closeModal() {
  modalOpen.value = false
  editingClinicId.value = null
  resetForm()
}

function openToggleModal(clinic: Clinic, action: 'activate' | 'deactivate') {
  pendingClinic.value = clinic
  pendingAction.value = action
  error.value = ''
  toggleModalOpen.value = true
}

function closeToggleModal() {
  toggleModalOpen.value = false
  pendingClinic.value = null
}

function validateForm() {
  const errors: Partial<Record<keyof ClinicPayload, string>> = {}

  if (!form.name.trim()) {
    errors.name = 'Informe o nome da clínica.'
  }

  if (form.document && form.document.length > 30) {
    errors.document = 'O documento deve ter no máximo 30 caracteres.'
  }

  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Informe um e-mail válido.'
  }

  const phoneError = validatePhone(form.phone)
  if (phoneError) {
    errors.phone = phoneError
  }

  return errors
}

function applyFieldErrors(errors: Partial<Record<keyof ClinicPayload, string>>) {
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

function notify(message: string, type: 'success' | 'error' = 'success') {
  toastType.value = type
  toastMessage.value = message

  if (toastTimeout) clearTimeout(toastTimeout)
  toastTimeout = setTimeout(() => {
    toastMessage.value = ''
  }, 3000)
}

async function loadClinics() {
  loading.value = true
  error.value = ''

  try {
    const response = await listarClinicas({
      search: search.value,
      per_page: pagination.value.per_page,
      page: currentPage.value,
    })

    clinics.value = response.data
    pagination.value = {
      current_page: response.meta.current_page,
      last_page: response.meta.last_page,
      per_page: response.meta.per_page,
      total: response.meta.total,
    }
  } catch {
    error.value = 'Não foi possível carregar as clínicas.'
  } finally {
    loading.value = false
  }
}

async function submitClinic() {
  const validationErrors = validateForm()
  if (Object.keys(validationErrors).length > 0) {
    applyFieldErrors(validationErrors)
    return
  }

  saving.value = true
  error.value = ''

  try {
    const payload = {
      name: form.name.trim(),
      document: form.document.trim(),
      email: form.email.trim(),
      phone: normalizePhone(form.phone),
    }

    if (editingClinicId.value) {
      await atualizarClinica(editingClinicId.value, payload)
      notify('Clínica atualizada com sucesso.')
    } else {
      await criarClinica(payload)
      notify('Clínica cadastrada com sucesso.')
    }

    closeModal()
    await loadClinics()
  } catch (requestError) {
    if (isAxiosError(requestError) && requestError.response?.status === 422) {
      const errors = requestError.response.data?.errors ?? {}
      applyFieldErrors({
        name: errors.name?.[0],
        document: errors.document?.[0],
        email: errors.email?.[0],
        phone: errors.phone?.[0],
      })

      error.value = ''
      return
    }

    error.value = errorMessageFromResponse(requestError, 'Não foi possível salvar a clínica.')
  } finally {
    saving.value = false
  }
}

async function confirmToggle() {
  if (!pendingClinic.value) return

  toggling.value = true
  error.value = ''

  try {
    if (pendingAction.value === 'activate') {
      await ativarClinica(pendingClinic.value.id)
      notify('Clínica ativada com sucesso.')
    } else {
      await desativarClinica(pendingClinic.value.id)
      notify('Clínica desativada com sucesso.')
    }

    closeToggleModal()
    await loadClinics()
  } catch (requestError) {
    error.value = errorMessageFromResponse(requestError, 'Não foi possível alterar o status da clínica.')
  } finally {
    toggling.value = false
  }
}

function previousPage() {
  if (currentPage.value <= 1) return
  currentPage.value -= 1
}

function nextPage() {
  if (currentPage.value >= totalPages.value) return
  currentPage.value += 1
}

function statusTone(active: boolean) {
  return active ? 'success' : 'danger'
}

function statusLabel(active: boolean) {
  return active ? 'Ativa' : 'Inativa'
}

function formatDate(value?: string) {
  if (!value) return '-'

  return new Date(value).toLocaleDateString('pt-BR')
}

watch(search, () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1
    return
  }

  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadClinics, 300)
})

watch(currentPage, () => {
  loadClinics()
})

onMounted(loadClinics)

onBeforeUnmount(() => {
  if (searchTimeout) clearTimeout(searchTimeout)
  if (toastTimeout) clearTimeout(toastTimeout)
})
</script>

<template>
  <AppLayout>
    <AppToast v-if="toastMessage" :message="toastMessage" :type="toastType" />

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-[#0F172A]">Clínicas</h1>
        <p class="mt-1 text-sm text-slate-500">Gestão centralizada das clínicas da plataforma</p>
      </div>
      <AppButton @click="openCreateModal">
        <Plus class="h-4 w-4" />
        Nova clínica
      </AppButton>
    </div>

    <p v-if="error" class="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</p>

    <div class="mb-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
      <AppInput
        v-model="search"
        label="Buscar clínicas"
        placeholder="Nome, documento, e-mail ou telefone"
      />
      <div class="rounded-lg border border-[#E2E8F0] bg-white px-4 py-3 text-sm text-slate-600">
        <strong class="block text-[#0F172A]">{{ pagination.total }}</strong>
        <span>Total de clínicas cadastradas</span>
      </div>
    </div>

    <div v-if="loading" class="rounded-lg border border-[#E2E8F0] bg-white p-8 text-center text-sm text-slate-500">
      <Loader2 class="mx-auto mb-3 h-5 w-5 animate-spin text-slate-400" />
      Carregando clínicas...
    </div>

    <template v-else>
      <div v-if="clinics.length === 0" class="rounded-lg border border-dashed border-[#CBD5E1] bg-white p-8 text-center">
        <Building2 class="mx-auto mb-3 h-8 w-8 text-slate-400" />
        <strong class="block text-sm text-[#0F172A]">Nenhuma clínica encontrada</strong>
        <p class="mt-1 text-sm text-slate-500">Crie a primeira clínica para começar a gestão centralizada.</p>
      </div>

      <section v-else class="grid gap-4">
        <AppTable>
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3 font-semibold">Clínica</th>
              <th class="px-4 py-3 font-semibold">Contato</th>
              <th class="px-4 py-3 font-semibold">Status</th>
              <th class="px-4 py-3 font-semibold">Criada em</th>
              <th class="px-4 py-3 font-semibold text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#E2E8F0] bg-white">
            <tr v-for="clinic in clinics" :key="clinic.id" class="align-top">
              <td class="px-4 py-4">
                <strong class="block text-sm text-[#0F172A]">{{ clinic.name }}</strong>
                <span class="mt-1 block text-xs text-slate-500">{{ clinic.document || 'Sem documento' }}</span>
              </td>
              <td class="px-4 py-4 text-sm text-slate-600">
                <span class="block">{{ clinic.email || 'Sem e-mail' }}</span>
                <span class="mt-1 block text-xs text-slate-500">{{ formatPhone(clinic.phone || '') || 'Sem telefone' }}</span>
              </td>
              <td class="px-4 py-4">
                <AppBadge :tone="statusTone(clinic.active)">
                  {{ statusLabel(clinic.active) }}
                </AppBadge>
              </td>
              <td class="px-4 py-4 text-sm text-slate-600">
                {{ formatDate(clinic.created_at) }}
              </td>
              <td class="px-4 py-4">
                <div class="flex flex-wrap justify-end gap-2">
                  <AppButton variant="secondary" @click="openEditModal(clinic)">
                    <Pencil class="h-4 w-4" />
                    Editar
                  </AppButton>
                  <AppButton
                    v-if="clinic.active"
                    variant="ghost"
                    @click="openToggleModal(clinic, 'deactivate')"
                  >
                    <PowerOff class="h-4 w-4" />
                    Desativar
                  </AppButton>
                  <AppButton
                    v-else
                    variant="ghost"
                    @click="openToggleModal(clinic, 'activate')"
                  >
                    <Power class="h-4 w-4" />
                    Ativar
                  </AppButton>
                </div>
              </td>
            </tr>
          </tbody>
        </AppTable>

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#E2E8F0] bg-white px-4 py-3 text-sm text-slate-600">
          <span>{{ showingRange }}</span>
          <div class="flex items-center gap-2">
            <AppButton variant="secondary" :disabled="currentPage <= 1" @click="previousPage">
              <ChevronLeft class="h-4 w-4" />
              Anterior
            </AppButton>
            <span class="min-w-24 text-center font-medium text-[#0F172A]">
              Página {{ pagination.current_page }} de {{ pagination.last_page }}
            </span>
            <AppButton variant="secondary" :disabled="currentPage >= totalPages" @click="nextPage">
              Próxima
              <ChevronRight class="h-4 w-4" />
            </AppButton>
          </div>
        </div>
      </section>
    </template>

    <AppModal :open="modalOpen" :title="modalTitle" @close="closeModal">
      <form class="grid gap-5" @submit.prevent="submitClinic">
        <div class="grid gap-4 md:grid-cols-2">
          <AppInput v-model="form.name" label="Nome da clínica" required :error="formErrors.name" />
          <AppInput v-model="form.document" label="Documento" :error="formErrors.document" />
          <AppInput v-model="form.email" label="E-mail" type="email" :error="formErrors.email" />
          <AppPhoneInput v-model="form.phone" label="Telefone" :error="formErrors.phone" />
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
          <div class="flex items-start gap-3">
            <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-[#0F172A]" />
            <p>O status da clínica é gerenciado separadamente por confirmação para reduzir alterações acidentais.</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
          <AppButton variant="secondary" type="button" @click="closeModal">
            Cancelar
          </AppButton>
          <AppButton type="submit" :disabled="!canSubmit">
            <Save class="h-4 w-4" />
            {{ saving ? 'Salvando...' : 'Salvar clínica' }}
          </AppButton>
        </div>
      </form>
    </AppModal>

    <AppModal :open="toggleModalOpen" :title="toggleTitle" @close="closeToggleModal">
      <div class="grid gap-5">
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
          <strong class="block text-[#0F172A]">{{ pendingClinic?.name }}</strong>
          <p class="mt-1">{{ toggleDescription }}</p>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
          <AppButton variant="secondary" type="button" @click="closeToggleModal">
            Cancelar
          </AppButton>
          <AppButton :variant="pendingAction === 'activate' ? 'primary' : 'danger'" :disabled="toggling" @click="confirmToggle">
            {{ toggling ? 'Processando...' : toggleConfirmLabel }}
          </AppButton>
        </div>
      </div>
    </AppModal>
  </AppLayout>
</template>
