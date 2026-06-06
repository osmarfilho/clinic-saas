import api from './api'

export interface ClinicNotification {
  id: number
  title: string
  body?: string | null
  type: 'info' | 'success' | 'warning' | 'danger'
  read_at?: string | null
  created_at: string
}

interface NotificationResponse {
  unread_count: number
  notifications: {
    data: ClinicNotification[]
    total: number
  }
}

export async function listarNotificacoes() {
  const { data } = await api.get<NotificationResponse>('/notifications')

  return data
}

export async function marcarNotificacaoComoLida(id: number) {
  const { data } = await api.post<ClinicNotification>(`/notifications/${id}/read`)

  return data
}

export async function marcarTodasComoLidas() {
  const { data } = await api.post<{ message: string }>('/notifications/read-all')

  return data
}
