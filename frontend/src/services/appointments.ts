import api from './api'
import type { Patient } from './patient'

export type AppointmentStatus = 'scheduled' | 'confirmed' | 'completed' | 'canceled' | 'no_show'

export interface Appointment {
  id: number
  patient_id?: number | null
  patient?: Pick<Patient, 'id' | 'nome' | 'cpf' | 'telefone' | 'email'> | null
  title: string
  professional?: string | null
  type: string
  starts_at: string
  ends_at?: string | null
  status: AppointmentStatus
  price: string | number
  notes?: string | null
}

export interface AppointmentPayload {
  patient_id?: number | null
  title: string
  professional: string
  type: string
  starts_at: string
  ends_at: string | null
  status: AppointmentStatus
  price: number
  notes: string
}

interface PaginatedAppointments {
  data: Appointment[]
  current_page: number
  last_page: number
  total: number
}

export async function listarAgendamentos(params: { search?: string; date?: string; status?: string } = {}) {
  const { data } = await api.get<PaginatedAppointments>('/appointments', { params })

  return data
}

export async function criarAgendamento(payload: AppointmentPayload) {
  const { data } = await api.post<Appointment>('/appointments', payload)

  return data
}

export async function atualizarAgendamento(id: number, payload: Partial<AppointmentPayload>) {
  const { data } = await api.put<Appointment>(`/appointments/${id}`, payload)

  return data
}

export async function removerAgendamento(id: number) {
  const { data } = await api.delete<{ message: string }>(`/appointments/${id}`)

  return data
}
