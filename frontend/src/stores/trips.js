import { defineStore } from 'pinia'
import api from '@/services/api'

export const useTripsStore = defineStore('trips', {
  state: () => ({
    trips: [],
    myTrips: [],
    currentTrip: null,
    loading: false,
    error: null,
    pagination: {
      page: 1,
      limit: 10,
      total: 0,
      totalPages: 0
    },
    filters: {
      origin: '',
      destination: '',
      date: '',
      minPrice: '',
      maxPrice: ''
    }
  }),

  getters: {
    activeTrips: (state) => state.trips.filter(t => t.status === 'aktif'),
    hasMorePages: (state) => state.pagination.page < state.pagination.totalPages
  },

  actions: {
    async fetchTrips(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/trips', { params: { ...this.filters, ...params } })
        this.trips = response.data.data || []
        if (response.data.pagination) {
          this.pagination = response.data.pagination
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal memuat perjalanan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchMyTrips() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/trips/my-trips')
        this.myTrips = response.data.data || []
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal memuat perjalanan saya'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchTripById(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get(`/trips/${id}`)
        this.currentTrip = response.data.data
        return this.currentTrip
      } catch (error) {
        this.error = error.response?.data?.message || 'Perjalanan tidak ditemukan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createTrip(tripData) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/trips', tripData)
        const newTrip = response.data.data
        this.myTrips.unshift(newTrip)
        return newTrip
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal membuat perjalanan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateTrip(id, tripData) {
      this.loading = true
      this.error = null
      try {
        const response = await api.put(`/trips/${id}`, tripData)
        const updatedTrip = response.data.data
        
        // Update in myTrips
        const index = this.myTrips.findIndex(t => t.id === id)
        if (index !== -1) {
          this.myTrips[index] = updatedTrip
        }
        
        // Update currentTrip if same
        if (this.currentTrip?.id === id) {
          this.currentTrip = updatedTrip
        }
        
        return updatedTrip
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal mengupdate perjalanan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateTripStatus(id, status) {
      this.loading = true
      this.error = null
      try {
        const response = await api.patch(`/trips/${id}/status`, { status })
        const updatedTrip = response.data.data
        
        const index = this.myTrips.findIndex(t => t.id === id)
        if (index !== -1) {
          this.myTrips[index] = { ...this.myTrips[index], status }
        }
        
        if (this.currentTrip?.id === id) {
          this.currentTrip = { ...this.currentTrip, status }
        }
        
        return updatedTrip
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal mengubah status'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteTrip(id) {
      this.loading = true
      this.error = null
      try {
        await api.delete(`/trips/${id}`)
        this.myTrips = this.myTrips.filter(t => t.id !== id)
        if (this.currentTrip?.id === id) {
          this.currentTrip = null
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal menghapus perjalanan'
        throw error
      } finally {
        this.loading = false
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters }
    },

    clearFilters() {
      this.filters = {
        origin: '',
        destination: '',
        date: '',
        minPrice: '',
        maxPrice: ''
      }
    },

    clearCurrentTrip() {
      this.currentTrip = null
    }
  }
})
