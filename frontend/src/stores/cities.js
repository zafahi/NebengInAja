import { defineStore } from 'pinia'
import api from '@/services/api'

export const useCitiesStore = defineStore('cities', {
  state: () => ({
    cities: [],
    loading: false,
    error: null
  }),

  getters: {
    getCityById: (state) => (id) => {
      return state.cities.find(c => c.id === id)
    },
    getCityName: (state) => (id) => {
      const city = state.cities.find(c => c.id === id)
      return city ? city.nama : ''
    }
  },

  actions: {
    async fetchCities() {
      // Don't fetch if already loaded
      if (this.cities.length > 0) return this.cities
      
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/cities')
        this.cities = response.data.data || []
        return this.cities
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal memuat daftar kota'
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
