<template>
  <div class="my-bookings-page">
    <div class="page-header">
      <div class="container">
        <h1 class="page-title">Pemesanan Saya</h1>
        <p class="page-subtitle">Riwayat pemesanan tumpangan Anda</p>
      </div>
    </div>
    
    <div class="container">
      <!-- Filter Tabs -->
      <div class="filter-tabs">
        <button 
          v-for="tab in tabs" 
          :key="tab.value"
          @click="activeTab = tab.value"
          :class="['tab-btn', { active: activeTab === tab.value }]"
        >
          {{ tab.label }}
        </button>
      </div>
      
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
        <p>Memuat data...</p>
      </div>
      
      <div v-else-if="filteredBookings.length === 0" class="empty-state">
        <div class="empty-state-icon">📋</div>
        <h3 class="empty-state-title">Belum Ada Pemesanan</h3>
        <p class="empty-state-text">Anda belum melakukan pemesanan tumpangan.</p>
        <router-link to="/trips" class="btn btn-primary">Cari Tumpangan</router-link>
      </div>
      
      <div v-else class="bookings-list">
        <div v-for="booking in filteredBookings" :key="booking.id" class="booking-item card">
          <div class="card-body">
            <div class="booking-header">
              <div class="booking-route">
                <span class="booking-city">{{ booking.origin_city }}</span>
                <span class="booking-arrow">→</span>
                <span class="booking-city">{{ booking.destination_city }}</span>
              </div>
              <div class="booking-badges">
                <span class="badge" :class="getStatusClass(booking.booking_status)">
                  {{ getStatusLabel(booking.booking_status) }}
                </span>
                <span class="badge" :class="getPaymentClass(booking.payment_status)">
                  {{ getPaymentLabel(booking.payment_status) }}
                </span>
              </div>
            </div>
            
            <div class="booking-info">
              <div class="booking-info-item">
                📅 {{ formatDate(booking.waktu_keberangkatan) }}
              </div>
              <div class="booking-info-item">
                🕐 {{ formatTime(booking.waktu_keberangkatan) }}
              </div>
              <div class="booking-info-item">
                💺 {{ booking.seat_count }} kursi
              </div>
              <div class="booking-info-item">
                📍 {{ booking.pickup_location }}
              </div>
            </div>
            
            <div class="booking-driver">
              <img :src="getDriverAvatar(booking)" :alt="booking.driver_nama" class="driver-avatar">
              <div class="driver-info">
                <div class="driver-name">{{ booking.driver_nama }}</div>
                <div class="driver-vehicle">
                  {{ booking.brand_nama }} {{ booking.model_nama }} • {{ booking.plate_number }}
                </div>
              </div>
              <div class="booking-fare">
                {{ formatRupiah(booking.fare) }}
              </div>
            </div>
            
            <div class="booking-actions">
              <router-link :to="`/bookings/${booking.id}`" class="btn btn-outline btn-sm">
                Lihat Detail
              </router-link>
              
              <template v-if="booking.booking_status === 'pending'">
                <button @click="cancelBooking(booking.id)" class="btn btn-danger btn-sm">
                  Batalkan
                </button>
              </template>
              
              <template v-if="booking.booking_status === 'diterima' && booking.payment_status === 'belum_bayar'">
                <button @click="confirmPayment(booking.id)" class="btn btn-primary btn-sm">
                  Konfirmasi Pembayaran
                </button>
              </template>
              
              <template v-if="booking.booking_status === 'selesai' && !booking.has_reviewed">
                <button @click="openReview(booking)" class="btn btn-secondary btn-sm">
                  Beri Review
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Review Modal -->
    <div v-if="showReviewModal" class="modal-overlay" @click.self="showReviewModal = false">
      <div class="modal-content">
        <h2>Beri Review</h2>
        <form @submit.prevent="submitReview">
          <div class="form-group">
            <label class="form-label">Rating</label>
            <div class="rating-input">
              <button 
                v-for="n in 5" 
                :key="n" 
                type="button"
                @click="reviewForm.rating = n"
                :class="['rating-star', { active: n <= reviewForm.rating }]"
              >
                ★
              </button>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Komentar</label>
            <textarea 
              v-model="reviewForm.komentar" 
              class="form-control" 
              rows="4"
              placeholder="Bagikan pengalaman perjalanan Anda..."
            ></textarea>
          </div>
          
          <div class="modal-actions">
            <button type="button" @click="showReviewModal = false" class="btn btn-outline">
              Batal
            </button>
            <button type="submit" class="btn btn-primary" :disabled="reviewLoading">
              {{ reviewLoading ? 'Mengirim...' : 'Kirim Review' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'

const loading = ref(true)
const bookings = ref([])
const activeTab = ref('all')
const showReviewModal = ref(false)
const reviewLoading = ref(false)
const selectedBooking = ref(null)

const reviewForm = ref({
  rating: 5,
  komentar: ''
})

const tabs = [
  { label: 'Semua', value: 'all' },
  { label: 'Menunggu', value: 'pending' },
  { label: 'Diterima', value: 'diterima' },
  { label: 'Dalam Perjalanan', value: 'dalam_perjalanan' },
  { label: 'Selesai', value: 'selesai' },
  { label: 'Dibatalkan', value: 'dibatalkan' }
]

const filteredBookings = computed(() => {
  let result = activeTab.value === 'all' 
    ? [...bookings.value] 
    : bookings.value.filter(b => b.booking_status === activeTab.value)
  
  // Sort by created_at descending (newest first)
  return result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

onMounted(async () => {
  await fetchBookings()
})

async function fetchBookings() {
  loading.value = true
  try {
    const response = await api.get('/bookings/my-bookings')
    if (response.data.success) {
      bookings.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch bookings:', error)
  } finally {
    loading.value = false
  }
}

async function cancelBooking(bookingId) {
  if (!confirm('Yakin ingin membatalkan pemesanan ini?')) return
  
  try {
    const response = await api.delete(`/bookings/${bookingId}`)
    if (response.data.success) {
      await fetchBookings()
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal membatalkan pemesanan')
  }
}

async function confirmPayment(bookingId) {
  if (!confirm('Konfirmasi bahwa Anda sudah melakukan pembayaran?')) return
  
  try {
    const response = await api.put(`/bookings/${bookingId}/payment`, {
      payment_status: 'menunggu_konfirmasi',
      payment_method: 'transfer'
    })
    if (response.data.success) {
      await fetchBookings()
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal mengkonfirmasi pembayaran')
  }
}

function openReview(booking) {
  selectedBooking.value = booking
  reviewForm.value = { rating: 5, komentar: '' }
  showReviewModal.value = true
}

async function submitReview() {
  reviewLoading.value = true
  
  try {
    const response = await api.post('/reviews', {
      booking_id: selectedBooking.value.id,
      rating: reviewForm.value.rating,
      komentar: reviewForm.value.komentar
    })
    
    if (response.data.success) {
      showReviewModal.value = false
      await fetchBookings()
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal mengirim review')
  } finally {
    reviewLoading.value = false
  }
}

function getDriverAvatar(booking) {
  return booking.driver_foto && booking.driver_foto !== 'default.png'
    ? `/uploads/${booking.driver_foto}`
    : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(booking.driver_nama) + '&background=4F46E5&color=fff'
}

function getStatusClass(status) {
  const map = {
    'pending': 'badge-warning',
    'diterima': 'badge-success',
    'ditolak': 'badge-danger',
    'dibatalkan': 'badge-danger',
    'dalam_perjalanan': 'badge-info',
    'selesai': 'badge-primary'
  }
  return map[status] || 'badge-primary'
}

function getStatusLabel(status) {
  const map = {
    'pending': 'Menunggu Konfirmasi',
    'diterima': 'Diterima',
    'ditolak': 'Ditolak',
    'dibatalkan': 'Dibatalkan',
    'dalam_perjalanan': 'Dalam Perjalanan',
    'selesai': 'Selesai'
  }
  return map[status] || status
}

function getPaymentClass(status) {
  const map = {
    'belum_bayar': 'badge-warning',
    'menunggu_konfirmasi': 'badge-info',
    'berhasil': 'badge-success',
    'gagal': 'badge-danger',
    'refund': 'badge-warning'
  }
  return map[status] || 'badge-primary'
}

function getPaymentLabel(status) {
  const map = {
    'belum_bayar': 'Belum Bayar',
    'menunggu_konfirmasi': 'Menunggu Konfirmasi',
    'berhasil': 'Lunas',
    'gagal': 'Gagal',
    'refund': 'Refund'
  }
  return map[status] || status
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
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
.filter-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.tab-btn {
  padding: 10px 20px;
  border: 1px solid var(--border-color);
  background: var(--white);
  border-radius: var(--radius);
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.tab-btn:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.tab-btn.active {
  background: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--white);
}

.bookings-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.booking-item {
  transition: box-shadow 0.2s;
}

.booking-item:hover {
  box-shadow: var(--shadow-md);
}

.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 12px;
}

.booking-route {
  display: flex;
  align-items: center;
  gap: 12px;
}

.booking-city {
  font-weight: 600;
  font-size: 18px;
}

.booking-arrow {
  color: var(--gray-color);
}

.booking-badges {
  display: flex;
  gap: 8px;
}

.booking-info {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 16px;
  color: var(--gray-color);
  font-size: 14px;
}

.booking-driver {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: var(--light-gray);
  border-radius: var(--radius);
  margin-bottom: 16px;
}

.driver-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.driver-info {
  flex: 1;
}

.driver-name {
  font-weight: 600;
}

.driver-vehicle {
  font-size: 14px;
  color: var(--gray-color);
}

.booking-fare {
  font-size: 20px;
  font-weight: 700;
  color: var(--primary-color);
}

.booking-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: var(--white);
  border-radius: var(--radius-lg);
  padding: 30px;
  max-width: 500px;
  width: 100%;
}

.modal-content h2 {
  margin-bottom: 24px;
}

.rating-input {
  display: flex;
  gap: 8px;
}

.rating-star {
  font-size: 32px;
  background: none;
  border: none;
  color: var(--border-color);
  cursor: pointer;
  transition: color 0.2s;
}

.rating-star.active,
.rating-star:hover {
  color: var(--warning-color);
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

@media (max-width: 576px) {
  .booking-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .booking-driver {
    flex-direction: column;
    text-align: center;
  }
  
  .booking-fare {
    margin-top: 12px;
  }
}
</style>
