import { defineStore } from 'pinia'
import api from '@/services/api'

export const useVehiclesStore = defineStore('vehicles', {
  state: () => ({
    vehicles: [],
    brands: [],
    models: [],
    currentVehicle: null,
    loading: false,
    error: null
  }),

  getters: {
    getVehicleById: (state) => (id) => {
      return state.vehicles.find(v => v.id === id)
    },
    getModelsByBrand: (state) => (brandId) => {
      return state.models.filter(m => m.brand_id === parseInt(brandId))
    }
  },

  actions: {
    async fetchMyVehicles() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/vehicles')
        this.vehicles = response.data.data || []
        return this.vehicles
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal memuat kendaraan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchBrands() {
      if (this.brands.length > 0) return this.brands
      
      try {
        const response = await api.get('/brands')
        this.brands = response.data.data || []
        return this.brands
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal memuat merk kendaraan'
        throw error
      }
    },

    async fetchModels(brandId) {
      try {
        const response = await api.get(`/brands/${brandId}/models`)
        this.models = response.data.data || []
        return this.models
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal memuat model kendaraan'
        throw error
      }
    },

    async addVehicle(vehicleData) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/vehicles', vehicleData)
        const newVehicle = response.data.data
        this.vehicles.push(newVehicle)
        return newVehicle
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal menambah kendaraan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateVehicle(id, vehicleData) {
      this.loading = true
      this.error = null
      try {
        const response = await api.put(`/vehicles/${id}`, vehicleData)
        const updatedVehicle = response.data.data
        
        const index = this.vehicles.findIndex(v => v.id === id)
        if (index !== -1) {
          this.vehicles[index] = updatedVehicle
        }
        
        return updatedVehicle
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal mengupdate kendaraan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteVehicle(id) {
      this.loading = true
      this.error = null
      try {
        await api.delete(`/vehicles/${id}`)
        this.vehicles = this.vehicles.filter(v => v.id !== id)
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal menghapus kendaraan'
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
