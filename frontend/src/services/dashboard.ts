import api from './api'
import type { Appointment } from './appointments'
import type { ClinicNotification } from './notifications'

export interface DashboardData {
  metrics: {
    active_patients: number
    appointments_today: number
    scheduled_today: number
    completed_today: number
    no_show_month: number
    cancelled_month: number
    monthly_revenue: number
    monthly_expenses: number
    occupancy_rate: number
  }
  indicators: {
    scheduled_today: number
    completed_today: number
    no_show_month: number
    cancelled_month: number
  }
  schedule: Appointment[]
  activities: ClinicNotification[]
}

export async function carregarDashboard() {
  const { data } = await api.get<DashboardData>('/dashboard')

  return data
}
