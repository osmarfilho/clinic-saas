import api from './api'

export interface ClinicSettings {
  clinic_name: string
  document: string
  phone: string
  email: string
  address: string
  opening_time: string
  closing_time: string
  appointment_duration: string
  daily_capacity: string
  average_wait_minutes: string
  satisfaction_rate: string
}

export async function carregarConfiguracoes() {
  const { data } = await api.get<ClinicSettings>('/settings')

  return data
}

export async function salvarConfiguracoes(payload: ClinicSettings) {
  const { data } = await api.put<ClinicSettings>('/settings', payload)

  return data
}
