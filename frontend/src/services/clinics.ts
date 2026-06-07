import api from './api'

export interface Clinic {
  id: number
  name: string
  document?: string | null
  email?: string | null
  phone?: string | null
  active: boolean
  created_at?: string
  updated_at?: string
}

export interface ClinicPayload {
  name: string
  document: string
  email: string
  phone: string
}

export interface PaginatedClinics {
  data: Clinic[]
  meta: {
    current_page: number
    from: number | null
    last_page: number
    links: Array<{
      url: string | null
      label: string
      active: boolean
    }>
    path: string
    per_page: number
    to: number | null
    total: number
  }
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
}

export async function listarClinicas(params: { search?: string; per_page?: number; page?: number } = {}) {
  const { data } = await api.get<PaginatedClinics>('/super-admin/clinics', {
    params,
  })

  return data
}

export async function criarClinica(payload: ClinicPayload) {
  const { data } = await api.post<Clinic>('/super-admin/clinics', payload)

  return data
}

export async function atualizarClinica(id: number, payload: Partial<ClinicPayload>) {
  const { data } = await api.put<Clinic>(`/super-admin/clinics/${id}`, payload)

  return data
}

export async function ativarClinica(id: number) {
  const { data } = await api.patch<Clinic>(`/super-admin/clinics/${id}/activate`)

  return data
}

export async function desativarClinica(id: number) {
  const { data } = await api.patch<Clinic>(`/super-admin/clinics/${id}/deactivate`)

  return data
}
