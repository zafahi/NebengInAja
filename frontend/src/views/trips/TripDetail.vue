<template>
  <div class="trip-detail-page">
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Memuat detail trip...</p>
    </div>
    
    <div v-else-if="!trip" class="container">
      <div class="empty-state">
        <div class="empty-state-icon">❌</div>
        <h3 class="empty-state-title">Trip Tidak Ditemukan</h3>
        <p class="empty-state-text">Trip yang Anda cari tidak tersedia.</p>
        <router-link to="/trips" class="btn btn-primary">Kembali ke Daftar Trip</router-link>
      </div>
    </div>
    
    <template v-else>
      <div class="page-header">
        <div class="container">
          <router-link to="/trips" class="back-link">← Kembali</router-link>
          <div class="trip-route-header">
            <h1>{{ trip.origin_city }} → {{ trip.destination_city }}</h1>
            <span class="badge" :class="statusClass">{{ statusLabel }}</span>
          </div>
        </div>
      </div>
      
      <div class="container">
        <div class="trip-detail-grid">
          <!-- Main Info -->
          <div class="trip-main-info">
            <div class="card">
              <div class="card-header">
                <h2>Detail Perjalanan</h2>
              </div>
              <div class="card-body">
                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-label">📍 Titik Jemput</span>
                    <span class="info-value">{{ trip.titik_jemput }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">🏁 Titik Tujuan</span>
                    <span class="info-value">{{ trip.titik_tujuan }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">📅 Tanggal</span>
                    <span class="info-value">{{ formatDate(trip.waktu_keberangkatan) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">🕐 Waktu Berangkat</span>
                    <span class="info-value">{{ formatTime(trip.waktu_keberangkatan) }} WIB</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">💺 Kursi Tersedia</span>
                    <span class="info-value">{{ trip.available_seats }} dari {{ trip.max_passenger }} kursi</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">💰 Harga per Kursi</span>
                    <span class="info-value price">{{ formatRupiah(trip.seat_price) }}</span>
                  </div>
                </div>
                
                <div v-if="trip.deskripsi" class="trip-description">
                  <h3>Keterangan Tambahan</h3>
                  <p>{{ trip.deskripsi }}</p>
                </div>
              </div>
            </div>
            
            <!-- Vehicle Info -->
            <div class="card">
              <div class="card-header">
                <h2>Kendaraan</h2>
              </div>
              <div class="card-body">
                <div class="vehicle-info">
                  <div class="vehicle-icon">🚗</div>
                  <div class="vehicle-details">
                    <div class="vehicle-name">{{ trip.brand_nama }} {{ trip.model_nama }}</div>
                    <div class="vehicle-plate">{{ trip.plate_number }}</div>
                    <div class="vehicle-meta">
                      {{ trip.vehicle_warna }} • {{ trip.vehicle_tahun }} • Kapasitas {{ trip.kapasitas }} orang
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Sidebar -->
          <div class="trip-sidebar">
            <!-- Driver Info -->
            <div class="card driver-card">
              <div class="card-body">
                <div class="driver-profile">
                  <img :src="driverAvatar" :alt="trip.driver_nama" class="driver-avatar">
                  <div class="driver-info">
                    <router-link :to="`/driver/${trip.driver_user_id}`" class="driver-name-link">
                      <h3>{{ trip.driver_nama }}</h3>
                    </router-link>
                    <div class="driver-rating">
                      ⭐ {{ trip.driver_rating || '0.00' }} • {{ trip.driver_total_trips || 0 }} trip
                    </div>
                    <span v-if="trip.is_verified" class="badge badge-success">✓ Verified</span>
                  </div>
                </div>
                
                <router-link :to="`/driver/${trip.driver_user_id}`" class="btn btn-outline btn-block btn-sm mt-2">
                  Lihat Profil & Review
                </router-link>
                
                <div v-if="authStore.isAuthenticated && trip.driver_user_id !== authStore.user?.id" class="driver-contact mt-2">
                  <a :href="'tel:' + trip.driver_hp" class="btn btn-outline btn-block">
                    📞 {{ trip.driver_hp }}
                  </a>
                </div>
              </div>
            </div>
            
            <!-- Booking Form -->
            <div v-if="canBook" class="card">
              <div class="card-header">
                <h2>Pesan Tumpangan</h2>
              </div>
              <div class="card-body">
                <form @submit.prevent="handleBooking">
                  <div v-if="bookingError" class="alert alert-danger">
                    {{ bookingError }}
                  </div>
                  
                  <div v-if="bookingSuccess" class="alert alert-success">
                    {{ bookingSuccess }}
                  </div>
                  
                  <div class="form-group">
                    <label class="form-label">Jumlah Kursi</label>
                    <select v-model="bookingForm.seat_count" class="form-control">
                      <option v-for="n in trip.available_seats" :key="n" :value="n">
                        {{ n }} Kursi
                      </option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label class="form-label">Lokasi Penjemputan</label>
                    <input 
                      type="text" 
                      v-model="bookingForm.pickup_location" 
                      class="form-control"
                      placeholder="Alamat lengkap tempat dijemput"
                      required
                    >
                  </div>
                  
                  <div class="form-group">
                    <label class="form-label">Catatan (opsional)</label>
                    <textarea 
                      v-model="bookingForm.catatan" 
                      class="form-control"
                      placeholder="Catatan tambahan untuk driver"
                      rows="3"
                    ></textarea>
                  </div>
                  
                  <div class="booking-total">
                    <span>Total Biaya</span>
                    <span class="total-price">{{ formatRupiah(totalFare) }}</span>
                  </div>
                  
                  <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="bookingLoading">
                    {{ bookingLoading ? 'Memproses...' : 'Pesan Sekarang' }}
                  </button>
                </form>
              </div>
            </div>
            
            <div v-else-if="!authStore.isAuthenticated" class="card">
              <div class="card-body text-center">
                <p class="mb-2">Login untuk memesan tumpangan ini</p>
                <router-link :to="{ name: 'Login', query: { redirect: $route.fullPath }}" class="btn btn-primary btn-block">
                  Login
                </router-link>
              </div>
            </div>
            
            <div v-else-if="trip.driver_user_id === authStore.user?.id" class="card">
              <div class="card-body text-center">
                <p class="text-gray">Ini adalah trip Anda</p>
                <router-link to="/my-trips" class="btn btn-outline btn-block">
                  Kelola Trip Saya
                </router-link>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Bookings List (for Driver) -->
        <div v-if="isOwner && bookings.length > 0" class="bookings-section">
          <div class="card">
            <div class="card-header">
              <h2>📋 Daftar Pemesanan ({{ bookings.length }})</h2>
            </div>
            <div class="card-body">
              <div class="bookings-list">
                <div v-for="booking in bookings" :key="booking.id" class="booking-item">
                  <div class="booking-passenger">
                    <img :src="getPassengerAvatar(booking)" :alt="booking.passenger_nama" class="passenger-avatar">
                    <div class="passenger-info">
                      <h4>{{ booking.passenger_nama }}</h4>
                      <p>{{ booking.passenger_hp }}</p>
                    </div>
                  </div>
                  
                  <div class="booking-details">
                    <div class="booking-detail-item">
                      <span class="detail-label">Kursi:</span>
                      <span>{{ booking.seat_count }}</span>
                    </div>
                    <div class="booking-detail-item">
                      <span class="detail-label">Lokasi Jemput:</span>
                      <span>{{ booking.pickup_location }}</span>
                    </div>
                    <div class="booking-detail-item">
                      <span class="detail-label">Total:</span>
                      <span class="booking-fare">{{ formatRupiah(booking.fare) }}</span>
                    </div>
                    <div class="booking-detail-item">
                      <span class="detail-label">Pembayaran:</span>
                      <span class="badge badge-sm" :class="getPaymentStatusClass(booking.payment_status)">
                        {{ getPaymentStatusLabel(booking.payment_status) }}
                      </span>
                    </div>
                    <div v-if="booking.catatan" class="booking-detail-item">
                      <span class="detail-label">Catatan:</span>
                      <span>{{ booking.catatan }}</span>
                    </div>
                  </div>
                  
                  <div class="booking-status-actions">
                    <span class="badge" :class="getBookingStatusClass(booking.booking_status)">
                      {{ getBookingStatusLabel(booking.booking_status) }}
                    </span>
                    
                    <!-- Booking Status Actions -->
                    <div v-if="booking.booking_status === 'pending'" class="booking-actions">
                      <button @click="updateBookingStatus(booking.id, 'diterima')" class="btn btn-success btn-sm">
                        ✓ Terima
                      </button>
                      <button @click="updateBookingStatus(booking.id, 'ditolak')" class="btn btn-danger btn-sm">
                        ✕ Tolak
                      </button>
                    </div>
                    
                    <!-- Payment Confirmation (for driver when trip is completed and payment is pending confirmation) -->
                    <div v-if="trip.status === 'selesai' && booking.payment_status === 'menunggu_konfirmasi'" class="booking-actions mt-2">
                      <button @click="confirmPayment(booking.id, 'berhasil')" class="btn btn-success btn-sm">
                        💰 Konfirmasi Bayar
                      </button>
                      <button @click="confirmPayment(booking.id, 'gagal')" class="btn btn-danger btn-sm">
                        ✕ Tolak Pembayaran
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else-if="isOwner && bookings.length === 0" class="bookings-section">
          <div class="card">
            <div class="card-body text-center">
              <div class="empty-state-icon">📭</div>
              <p class="text-gray">Belum ada pemesanan untuk trip ini</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const trip = ref(null)
const bookings = ref([])
const bookingLoading = ref(false)
const bookingError = ref('')
const bookingSuccess = ref('')

const bookingForm = ref({
  seat_count: 1,
  pickup_location: '',
  catatan: ''
})

const isOwner = computed(() => {
  return authStore.isAuthenticated && trip.value?.driver_user_id === authStore.user?.id
})

const driverAvatar = computed(() => {
  if (!trip.value) return ''
  return trip.value.driver_foto && trip.value.driver_foto !== 'default.png'
    ? `/uploads/${trip.value.driver_foto}`
    : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(trip.value.driver_nama) + '&background=4F46E5&color=fff&size=128'
})

const canBook = computed(() => {
  if (!authStore.isAuthenticated) return false
  if (!trip.value) return false
  if (trip.value.driver_user_id === authStore.user?.id) return false
  if (trip.value.available_seats === 0) return false
  if (!['menunggu', 'aktif'].includes(trip.value.status)) return false
  return true
})

const totalFare = computed(() => {
  if (!trip.value) return 0
  return trip.value.seat_price * bookingForm.value.seat_count
})

const statusClass = computed(() => {
  if (!trip.value) return ''
  const statusMap = {
    'menunggu': 'badge-warning',
    'aktif': 'badge-success',
    'dalam_perjalanan': 'badge-info',
    'selesai': 'badge-primary',
    'dibatalkan': 'badge-danger'
  }
  return statusMap[trip.value.status] || 'badge-primary'
})

const statusLabel = computed(() => {
  if (!trip.value) return ''
  const statusMap = {
    'menunggu': 'Menunggu',
    'aktif': 'Aktif',
    'dalam_perjalanan': 'Dalam Perjalanan',
    'selesai': 'Selesai',
    'dibatalkan': 'Dibatalkan'
  }
  return statusMap[trip.value.status] || trip.value.status
})

onMounted(async () => {
  await fetchTrip()
})

async function fetchTrip() {
  try {
    const response = await api.get(`/trips/${route.params.id}`)
    if (response.data.success) {
      trip.value = response.data.data
      
      // Fetch bookings if owner
      if (trip.value.driver_user_id === authStore.user?.id) {
        await fetchBookings()
      }
    }
  } catch (error) {
    console.error('Failed to fetch trip:', error)
  } finally {
    loading.value = false
  }
}

async function fetchBookings() {
  try {
    const response = await api.get(`/trips/${route.params.id}/bookings`)
    if (response.data.success) {
      bookings.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch bookings:', error)
  }
}

async function updateBookingStatus(bookingId, status) {
  const action = status === 'diterima' ? 'menerima' : 'menolak'
  if (!confirm(`Yakin ingin ${action} pemesanan ini?`)) return
  
  try {
    const response = await api.put(`/bookings/${bookingId}/status`, { booking_status: status })
    if (response.data.success) {
      await fetchBookings()
      await fetchTrip() // Refresh trip data (available seats might change)
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal mengubah status')
  }
}

function getPassengerAvatar(booking) {
  return booking.passenger_foto && booking.passenger_foto !== 'default.png'
    ? `/uploads/${booking.passenger_foto}`
    : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(booking.passenger_nama) + '&background=10B981&color=fff&size=64'
}

function getBookingStatusClass(status) {
  const map = {
    'pending': 'badge-warning',
    'diterima': 'badge-success',
    'ditolak': 'badge-danger',
    'dibatalkan': 'badge-danger',
    'dalam_perjalanan': 'badge-info',
    'selesai': 'badge-primary'
  }
  return map[status] || 'badge-secondary'
}

function getBookingStatusLabel(status) {
  const map = {
    'pending': 'Menunggu',
    'diterima': 'Diterima',
    'ditolak': 'Ditolak',
    'dibatalkan': 'Dibatalkan',
    'dalam_perjalanan': 'Dalam Perjalanan',
    'selesai': 'Selesai'
  }
  return map[status] || status
}

function getPaymentStatusClass(status) {
  const map = {
    'belum_bayar': 'badge-danger',
    'menunggu_konfirmasi': 'badge-warning',
    'berhasil': 'badge-success',
    'gagal': 'badge-danger',
    'refund': 'badge-info'
  }
  return map[status] || 'badge-secondary'
}

function getPaymentStatusLabel(status) {
  const map = {
    'belum_bayar': 'Belum Bayar',
    'menunggu_konfirmasi': 'Menunggu Konfirmasi',
    'berhasil': 'Lunas',
    'gagal': 'Gagal',
    'refund': 'Refund'
  }
  return map[status] || status
}

async function confirmPayment(bookingId, status) {
  const action = status === 'berhasil' ? 'mengonfirmasi' : 'menolak'
  if (!confirm(`Yakin ingin ${action} pembayaran ini?`)) return
  
  try {
    const response = await api.put(`/bookings/${bookingId}/payment`, { payment_status: status })
    if (response.data.success) {
      await fetchBookings()
      alert(status === 'berhasil' ? 'Pembayaran berhasil dikonfirmasi' : 'Pembayaran ditolak')
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal mengubah status pembayaran')
  }
}

async function handleBooking() {
  bookingLoading.value = true
  bookingError.value = ''
  bookingSuccess.value = ''
  
  try {
    const response = await api.post('/bookings', {
      trip_id: trip.value.id,
      seat_count: bookingForm.value.seat_count,
      pickup_location: bookingForm.value.pickup_location,
      catatan: bookingForm.value.catatan
    })
    
    if (response.data.success) {
      bookingSuccess.value = response.data.message
      setTimeout(() => {
        router.push('/my-bookings')
      }, 2000)
    } else {
      bookingError.value = response.data.message
    }
  } catch (error) {
    bookingError.value = error.response?.data?.message || 'Gagal membuat pemesanan'
  } finally {
    bookingLoading.value = false
  }
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

function formatTime(dateString) {
  const date = new Date(dateString)
  return date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatRupiah(amount) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}
</script>

<style scoped>
.back-link {
  display: inline-block;
  margin-bottom: 16px;
  color: var(--gray-color);
}

.trip-route-header {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.trip-route-header h1 {
  font-size: 28px;
}

.trip-detail-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 30px;
}

.trip-main-info {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-label {
  font-size: 14px;
  color: var(--gray-color);
}

.info-value {
  font-size: 16px;
  font-weight: 500;
}

.info-value.price {
  font-size: 24px;
  font-weight: 700;
  color: var(--primary-color);
}

.trip-description {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid var(--border-color);
}

.trip-description h3 {
  font-size: 16px;
  margin-bottom: 12px;
}

.trip-description p {
  color: var(--gray-color);
  line-height: 1.7;
}

.vehicle-info {
  display: flex;
  align-items: center;
  gap: 20px;
}

.vehicle-icon {
  font-size: 48px;
}

.vehicle-name {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 4px;
}

.vehicle-plate {
  font-size: 14px;
  font-weight: 500;
  color: var(--primary-color);
  margin-bottom: 4px;
}

.vehicle-meta {
  font-size: 14px;
  color: var(--gray-color);
}

/* Driver Card */
.driver-profile {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.driver-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
}

.driver-info h3 {
  font-size: 18px;
  margin-bottom: 4px;
}

.driver-rating {
  font-size: 14px;
  color: var(--gray-color);
  margin-bottom: 8px;
}

/* Booking Form */
.booking-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: var(--light-gray);
  border-radius: var(--radius);
  margin-bottom: 20px;
}

.total-price {
  font-size: 24px;
  font-weight: 700;
  color: var(--primary-color);
}

/* Bookings Section (for Driver) */
.bookings-section {
  margin-top: 30px;
}

.bookings-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.booking-item {
  display: grid;
  grid-template-columns: 200px 1fr auto;
  gap: 20px;
  padding: 20px;
  background: var(--light-gray);
  border-radius: var(--radius);
  align-items: start;
}

.booking-passenger {
  display: flex;
  align-items: center;
  gap: 12px;
}

.passenger-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.passenger-info h4 {
  font-size: 16px;
  margin-bottom: 4px;
}

.passenger-info p {
  font-size: 13px;
  color: var(--gray-color);
  margin: 0;
}

.booking-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.booking-detail-item {
  font-size: 14px;
}

.detail-label {
  color: var(--gray-color);
  margin-right: 8px;
}

.booking-fare {
  font-weight: 600;
  color: var(--primary-color);
}

.booking-status-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
}

.booking-actions {
  display: flex;
  gap: 8px;
}

@media (max-width: 992px) {
  .trip-detail-grid {
    grid-template-columns: 1fr;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .booking-item {
    grid-template-columns: 1fr;
  }
  
  .booking-status-actions {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}
</style>
