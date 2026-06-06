import api from './api'
import type { Appointment } from './appointments'
import type { ClinicNotification } from './notifications'

export interface DashboardData {
  metrics: {
    active_patients: number
    appointments_today: number
    pending_today: number
    monthly_revenue: number
    monthly_expenses: number
    occupancy_rate: number
  }
  indicators: {
    average_wait_minutes: number
    satisfaction_rate: number
    no_show_rate: number
  }
  schedule: Appointment[]
  activities: ClinicNotification[]
}

export async function carregarDashboard() {
  const { data } = await api.get<DashboardData>('/dashboard')

  return data
}
