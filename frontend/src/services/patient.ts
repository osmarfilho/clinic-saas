import api from './api'

export interface Patient {
  id: number
  nome: string
  cpf: string
  telefone?: string | null
  email?: string | null
  data_nascimento?: string | null
  convenio?: string | null
  cep?: string | null
  endereco?: string | null
  numero?: string | null
  bairro?: string | null
  cidade?: string | null
  estado?: string | null
  observacoes?: string | null
  ativo: boolean
  created_at?: string
  updated_at?: string
}

export interface PatientPayload {
  nome: string
  cpf: string
  telefone: string
  email: string
  data_nascimento: string
  convenio: string
  cep: string
  endereco: string
  numero: string
  bairro: string
  cidade: string
  estado: string
  observacoes: string
  ativo?: boolean
}

interface PaginatedPatients {
  data: Patient[]
  current_page: number
  last_page: number
  total: number
}

export async function listarPacientes(search = '') {
  const { data } = await api.get<PaginatedPatients>('/patients', {
    params: { search },
  })

  return data
}

export async function criarPaciente(payload: PatientPayload) {
  const { data } = await api.post<Patient>('/patients', payload)

  return data
}

export async function atualizarPaciente(id: number, payload: Partial<PatientPayload>) {
  const { data } = await api.put<Patient>(`/patients/${id}`, payload)

  return data
}

export async function removerPaciente(id: number) {
  const { data } = await api.delete<{ message: string }>(`/patients/${id}`)

  return data
}
