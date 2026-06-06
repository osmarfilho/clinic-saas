import api from './api'
import type { Patient } from './patient'

export type TransactionType = 'income' | 'expense'
export type TransactionStatus = 'pending' | 'paid' | 'canceled'

export interface FinancialTransaction {
  id: number
  patient_id?: number | null
  patient?: Pick<Patient, 'id' | 'nome' | 'cpf' | 'email'> | null
  description: string
  type: TransactionType
  category?: string | null
  amount: string | number
  due_date: string
  paid_at?: string | null
  status: TransactionStatus
  payment_method?: string | null
  notes?: string | null
}

export interface FinancialTransactionPayload {
  patient_id?: number | null
  description: string
  type: TransactionType
  category: string
  amount: number
  due_date: string
  paid_at: string | null
  status: TransactionStatus
  payment_method: string
  notes: string
}

export interface FinancialSummary {
  paid_income: number
  paid_expenses: number
  pending_income: number
  pending_expenses: number
  current_balance: number
  forecast_balance: number
}

interface PaginatedTransactions {
  data: FinancialTransaction[]
  current_page: number
  last_page: number
  total: number
  summary: FinancialSummary
}

export async function listarLancamentos(params: { search?: string; type?: string; status?: string } = {}) {
  const { data } = await api.get<PaginatedTransactions>('/financial-transactions', { params })

  return data
}

export async function criarLancamento(payload: FinancialTransactionPayload) {
  const { data } = await api.post<FinancialTransaction>('/financial-transactions', payload)

  return data
}

export async function atualizarLancamento(id: number, payload: Partial<FinancialTransactionPayload>) {
  const { data } = await api.put<FinancialTransaction>(`/financial-transactions/${id}`, payload)

  return data
}

export async function removerLancamento(id: number) {
  const { data } = await api.delete<{ message: string }>(`/financial-transactions/${id}`)

  return data
}
