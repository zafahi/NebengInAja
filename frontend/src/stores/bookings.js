import { defineStore } from 'pinia'
import api from '@/services/api'

export const useBookingsStore = defineStore('bookings', {
  state: () => ({
    bookings: [],
    currentBooking: null,
    loading: false,
    error: null
  }),

  getters: {
    pendingBookings: (state) => state.bookings.filter(b => b.booking_status === 'pending'),
    confirmedBookings: (state) => state.bookings.filter(b => b.booking_status === 'diterima'),
    completedBookings: (state) => state.bookings.filter(b => b.booking_status === 'selesai'),
    
    getBookingsByTrip: (state) => (tripId) => {
      return state.bookings.filter(b => b.trip_id === tripId)
    }
  },

  actions: {
    async fetchMyBookings() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/bookings')
        this.bookings = response.data.data || []
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal memuat pemesanan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchBookingById(id) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get(`/bookings/${id}`)
        this.currentBooking = response.data.data
        return this.currentBooking
      } catch (error) {
        this.error = error.response?.data?.message || 'Pemesanan tidak ditemukan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchTripBookings(tripId) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get(`/trips/${tripId}/bookings`)
        return response.data.data || []
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal memuat pemesanan trip'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createBooking(bookingData) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/bookings', bookingData)
        const newBooking = response.data.data
        this.bookings.unshift(newBooking)
        return newBooking
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal membuat pemesanan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateBookingStatus(id, status) {
      this.loading = true
      this.error = null
      try {
        const response = await api.patch(`/bookings/${id}/status`, { booking_status: status })
        const updatedBooking = response.data.data
        
        const index = this.bookings.findIndex(b => b.id === id)
        if (index !== -1) {
          this.bookings[index] = { ...this.bookings[index], booking_status: status }
        }
        
        if (this.currentBooking?.id === id) {
          this.currentBooking = { ...this.currentBooking, booking_status: status }
        }
        
        return updatedBooking
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal mengubah status'
        throw error
      } finally {
        this.loading = false
      }
    },

    async confirmPayment(id, paymentData) {
      this.loading = true
      this.error = null
      try {
        const response = await api.patch(`/bookings/${id}/payment`, paymentData)
        const updatedBooking = response.data.data
        
        const index = this.bookings.findIndex(b => b.id === id)
        if (index !== -1) {
          this.bookings[index] = updatedBooking
        }
        
        if (this.currentBooking?.id === id) {
          this.currentBooking = updatedBooking
        }
        
        return updatedBooking
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal memproses pembayaran'
        throw error
      } finally {
        this.loading = false
      }
    },

    async cancelBooking(id) {
      this.loading = true
      this.error = null
      try {
        await api.patch(`/bookings/${id}/status`, { booking_status: 'dibatalkan' })
        
        const index = this.bookings.findIndex(b => b.id === id)
        if (index !== -1) {
          this.bookings[index] = { ...this.bookings[index], booking_status: 'dibatalkan' }
        }
        
        if (this.currentBooking?.id === id) {
          this.currentBooking = { ...this.currentBooking, booking_status: 'dibatalkan' }
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal membatalkan pemesanan'
        throw error
      } finally {
        this.loading = false
      }
    },

    async addReview(bookingId, reviewData) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/reviews', {
          booking_id: bookingId,
          ...reviewData
        })
        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Gagal menambah review'
        throw error
      } finally {
        this.loading = false
      }
    },

    clearCurrentBooking() {
      this.currentBooking = null
    }
  }
})
