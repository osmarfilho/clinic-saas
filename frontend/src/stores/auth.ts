import { defineStore } from 'pinia'
import api from '@/services/api'

interface User {
  id: number
  name: string
  email: string
  roles?: Array<{ id: number; name: string }>
  permissions?: Array<{ id: number; name: string }>
  clinic?: {
    id: number
    name: string
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('clinic_token') as string | null,
    user: null as User | null,
    loading: false,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    isSuperAdmin: (state) => state.user?.roles?.some((role) => role.name === 'Super Admin') ?? false,
  },
  actions: {
    async login(email: string, password: string) {
      this.loading = true

      try {
        const { data } = await api.post<{ token: string; user: User }>('/auth/login', {
          email,
          password,
        })

        this.token = data.token
        this.user = data.user
        localStorage.setItem('clinic_token', data.token)
      } finally {
        this.loading = false
      }
    },
    async fetchMe() {
      if (!this.token) return

      const { data } = await api.get<{ user: User }>('/auth/me')
      this.user = data.user
    },
    async logout() {
      if (this.token) {
        await api.post('/auth/logout').catch(() => undefined)
      }

      this.token = null
      this.user = null
      localStorage.removeItem('clinic_token')
    },
  },
})
