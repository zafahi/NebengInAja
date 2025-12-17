<template>
  <div class="booking-detail-page">
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Memuat detail pemesanan...</p>
    </div>
    
    <div v-else-if="!booking" class="container">
      <div class="empty-state">
        <div class="empty-state-icon">❌</div>
        <h3 class="empty-state-title">Pemesanan Tidak Ditemukan</h3>
        <router-link to="/my-bookings" class="btn btn-primary">Kembali</router-link>
      </div>
    </div>
    
    <template v-else>
      <div class="page-header">
        <div class="container">
          <router-link to="/my-bookings" class="back-link">← Kembali</router-link>
          <h1 class="page-title">Detail Pemesanan #{{ booking.id }}</h1>
        </div>
      </div>
      
      <div class="container">
        <div class="detail-grid">
          <div class="detail-main">
            <!-- Status Card -->
            <div class="card status-card">
              <div class="card-body">
                <div class="status-row">
                  <div class="status-item">
                    <span class="status-label">Status Pemesanan</span>
                    <span class="badge badge-lg" :class="getStatusClass(booking.booking_status)">
                      {{ getStatusLabel(booking.booking_status) }}
                    </span>
                  </div>
                  <div class="status-item">
                    <span class="status-label">Status Pembayaran</span>
                    <span class="badge badge-lg" :class="getPaymentClass(booking.payment_status)">
                      {{ getPaymentLabel(booking.payment_status) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Trip Info -->
            <div class="card">
              <div class="card-header">
                <h2>Informasi Perjalanan</h2>
              </div>
              <div class="card-body">
                <div class="route-display">
                  <div class="route-point">
                    <div class="point-marker origin"></div>
                    <div class="point-info">
                      <span class="point-label">Kota Asal</span>
                      <span class="point-value">{{ booking.origin_city }}</span>
                      <span class="point-address">{{ booking.trip_titik_jemput }}</span>
                    </div>
                  </div>
                  <div class="route-line"></div>
                  <div class="route-point">
                    <div class="point-marker destination"></div>
                    <div class="point-info">
                      <span class="point-label">Kota Tujuan</span>
                      <span class="point-value">{{ booking.destination_city }}</span>
                      <span class="point-address">{{ booking.trip_titik_tujuan }}</span>
                    </div>
                  </div>
                </div>
                
                <div class="info-grid mt-3">
                  <div class="info-item">
                    <span class="info-label">📅 Tanggal</span>
                    <span class="info-value">{{ formatDate(booking.waktu_keberangkatan) }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">🕐 Waktu</span>
                    <span class="info-value">{{ formatTime(booking.waktu_keberangkatan) }} WIB</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Booking Details -->
            <div class="card">
              <div class="card-header">
                <h2>Detail Pemesanan</h2>
              </div>
              <div class="card-body">
                <div class="info-grid">
                  <div class="info-item">
                    <span class="info-label">📍 Lokasi Penjemputan</span>
                    <span class="info-value">{{ booking.pickup_location }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">🏁 Lokasi Turun</span>
                    <span class="info-value">{{ booking.dropoff_location || booking.trip_titik_tujuan }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">💺 Jumlah Kursi</span>
                    <span class="info-value">{{ booking.seat_count }} kursi</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">📝 Catatan</span>
                    <span class="info-value">{{ booking.catatan || '-' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="detail-sidebar">
            <!-- Driver Card -->
            <div class="card">
              <div class="card-header">
                <h2>Driver</h2>
              </div>
              <div class="card-body">
                <div class="driver-profile">
                  <img :src="driverAvatar" :alt="booking.driver_nama" class="driver-avatar">
                  <div class="driver-info">
                    <router-link :to="`/driver/${booking.driver_user_id}`" class="driver-name-link">
                      <h3>{{ booking.driver_nama }}</h3>
                    </router-link>
                    <div class="driver-rating">
                      ⭐ {{ booking.driver_rating || '0.00' }}
                    </div>
                    <span v-if="booking.is_verified" class="badge badge-success">✓ Verified</span>
                  </div>
                </div>
                <router-link :to="`/driver/${booking.driver_user_id}`" class="btn btn-outline btn-block btn-sm mt-2">
                  Lihat Profil & Review
                </router-link>
                <a :href="'tel:' + booking.driver_hp" class="btn btn-outline btn-block mt-2">
                  📞 {{ booking.driver_hp }}
                </a>
              </div>
            </div>
            
            <!-- Vehicle Card -->
            <div class="card">
              <div class="card-header">
                <h2>Kendaraan</h2>
              </div>
              <div class="card-body">
                <div class="vehicle-info">
                  <div class="vehicle-icon">🚗</div>
                  <div>
                    <div class="vehicle-name">{{ booking.brand_nama }} {{ booking.model_nama }}</div>
                    <div class="vehicle-plate">{{ booking.plate_number }}</div>
                    <div class="vehicle-color">{{ booking.vehicle_warna }}</div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Payment Card -->
            <div class="card">
              <div class="card-header">
                <h2>Pembayaran</h2>
              </div>
              <div class="card-body">
                <div class="payment-summary">
                  <div class="payment-row">
                    <span>{{ booking.seat_count }} Kursi</span>
                    <span>{{ formatRupiah(booking.fare) }}</span>
                  </div>
                  <div class="payment-total">
                    <span>Total</span>
                    <span class="total-amount">{{ formatRupiah(booking.fare) }}</span>
                  </div>
                </div>
                
                <div v-if="booking.payment_method" class="payment-method mt-2">
                  <span class="text-sm text-gray">Metode: {{ booking.payment_method }}</span>
                </div>
                
                <div v-if="booking.paid_at" class="payment-date mt-1">
                  <span class="text-sm text-gray">Dibayar: {{ formatDate(booking.paid_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Review Card -->
            <div class="card" v-if="booking.status === 'selesai'">
              <div class="card-header">
                <h2>Review Perjalanan</h2>
              </div>
              <div class="card-body">
                <!-- Existing Review Display -->
                <div v-if="existingReview" class="existing-review">
                  <div class="review-rating">
                    <span class="stars">
                      <span v-for="i in 5" :key="i" class="star" :class="{ filled: i <= existingReview.rating }">★</span>
                    </span>
                    <span class="rating-value">{{ existingReview.rating }}/5</span>
                  </div>
                  <p class="review-comment">{{ existingReview.comment || 'Tidak ada komentar' }}</p>
                  <span class="review-date text-sm text-gray">{{ formatDate(existingReview.created_at) }}</span>
                </div>
                
                <!-- Review Form -->
                <div v-else>
                  <p class="text-sm text-gray mb-2">Berikan review untuk perjalanan ini:</p>
                  <div class="rating-input mb-2">
                    <span 
                      v-for="i in 5" 
                      :key="i" 
                      class="star-input"
                      :class="{ filled: i <= reviewForm.rating }"
                      @click="reviewForm.rating = i"
                    >★</span>
                  </div>
                  <textarea 
                    v-model="reviewForm.comment" 
                    placeholder="Tulis komentar (opsional)..."
                    class="form-control mb-2"
                    rows="3"
                  ></textarea>
                  <button 
                    @click="submitReview" 
                    :disabled="reviewForm.rating === 0 || submittingReview"
                    class="btn btn-primary btn-block"
                  >
                    {{ submittingReview ? 'Mengirim...' : 'Kirim Review' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../services/api'

const route = useRoute()

const loading = ref(true)
const booking = ref(null)
const existingReview = ref(null)
const submittingReview = ref(false)
const reviewForm = ref({
  rating: 0,
  comment: ''
})

const driverAvatar = computed(() => {
  if (!booking.value) return ''
  return booking.value.driver_foto && booking.value.driver_foto !== 'default.png'
    ? `/uploads/${booking.value.driver_foto}`
    : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(booking.value.driver_nama) + '&background=4F46E5&color=fff&size=128'
})

onMounted(async () => {
  await fetchBooking()
})

async function fetchBooking() {
  try {
    const response = await api.get(`/bookings/${route.params.id}`)
    if (response.data.success) {
      booking.value = response.data.data
      // Check for existing review if status is selesai
      if (booking.value.status === 'selesai') {
        await checkExistingReview()
      }
    }
  } catch (error) {
    console.error('Failed to fetch booking:', error)
  } finally {
    loading.value = false
  }
}

async function checkExistingReview() {
  try {
    const response = await api.get(`/reviews/booking/${route.params.id}`)
    if (response.data.success && response.data.data) {
      existingReview.value = response.data.data
    }
  } catch (error) {
    // No review exists, that's fine
    console.log('No existing review found')
  }
}

async function submitReview() {
  if (reviewForm.value.rating === 0) {
    alert('Silakan pilih rating terlebih dahulu')
    return
  }
  
  submittingReview.value = true
  try {
    const response = await api.post('/reviews', {
      booking_id: booking.value.id,
      rating: reviewForm.value.rating,
      comment: reviewForm.value.comment
    })
    
    if (response.data.success) {
      alert('Review berhasil dikirim!')
      await checkExistingReview()
    } else {
      alert(response.data.message || 'Gagal mengirim review')
    }
  } catch (error) {
    console.error('Failed to submit review:', error)
    alert(error.response?.data?.message || 'Gagal mengirim review')
  } finally {
    submittingReview.value = false
  }
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

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 24px;
}

.detail-main {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.detail-sidebar {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.status-card {
  background: linear-gradient(135deg, var(--primary-color) 0%, #7C3AED 100%);
  color: var(--white);
}

.status-card .status-label {
  color: rgba(255, 255, 255, 0.9);
}

.status-card .badge {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  font-weight: 600;
}

.status-card .badge-success {
  background: rgba(16, 185, 129, 0.3);
  color: #6EE7B7;
}

.status-card .badge-warning {
  background: rgba(245, 158, 11, 0.3);
  color: #FCD34D;
}

.status-card .badge-danger {
  background: rgba(239, 68, 68, 0.3);
  color: #FCA5A5;
}

.status-card .badge-info {
  background: rgba(59, 130, 246, 0.3);
  color: #93C5FD;
}

.status-card .badge-primary {
  background: rgba(255, 255, 255, 0.25);
  color: #fff;
}

.status-row {
  display: flex;
  gap: 40px;
}

.status-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.status-label {
  font-size: 14px;
  opacity: 0.9;
}

.badge-lg {
  padding: 8px 16px;
  font-size: 14px;
}

.route-display {
  position: relative;
  padding-left: 30px;
}

.route-point {
  display: flex;
  gap: 16px;
  position: relative;
  padding: 16px 0;
}

.point-marker {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  position: absolute;
  left: -30px;
  top: 20px;
}

.point-marker.origin {
  background: var(--secondary-color);
}

.point-marker.destination {
  background: var(--primary-color);
}

.route-line {
  position: absolute;
  left: -23px;
  top: 40px;
  bottom: 40px;
  width: 2px;
  background: var(--border-color);
}

.point-label {
  font-size: 12px;
  color: var(--gray-color);
  display: block;
}

.point-value {
  font-size: 18px;
  font-weight: 600;
  display: block;
}

.point-address {
  font-size: 14px;
  color: var(--gray-color);
  display: block;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 14px;
  color: var(--gray-color);
}

.info-value {
  font-weight: 500;
}

/* Driver */
.driver-profile {
  display: flex;
  align-items: center;
  gap: 16px;
}

.driver-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
}

.driver-info h3 {
  margin-bottom: 4px;
}

.driver-rating {
  font-size: 14px;
  color: var(--gray-color);
  margin-bottom: 4px;
}

/* Vehicle */
.vehicle-info {
  display: flex;
  align-items: center;
  gap: 16px;
}

.vehicle-icon {
  font-size: 40px;
}

.vehicle-name {
  font-weight: 600;
}

.vehicle-plate {
  color: var(--primary-color);
  font-weight: 500;
}

.vehicle-color {
  font-size: 14px;
  color: var(--gray-color);
}

/* Payment */
.payment-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
}

.payment-total {
  display: flex;
  justify-content: space-between;
  padding-top: 16px;
  border-top: 1px solid var(--border-color);
  margin-top: 8px;
  font-weight: 600;
}

.total-amount {
  font-size: 20px;
  color: var(--primary-color);
}

/* Review Section */
.existing-review {
  padding: 16px;
  background: var(--bg-gray);
  border-radius: 8px;
}

.review-rating {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.stars .star {
  color: #ddd;
  font-size: 20px;
}

.stars .star.filled {
  color: #f59e0b;
}

.rating-value {
  font-weight: 600;
  color: var(--text-color);
}

.review-comment {
  margin-bottom: 8px;
  color: var(--text-color);
  line-height: 1.5;
}

.review-date {
  font-size: 12px;
}

/* Rating Input */
.rating-input {
  display: flex;
  gap: 4px;
}

.star-input {
  font-size: 32px;
  color: #ddd;
  cursor: pointer;
  transition: color 0.2s;
}

.star-input:hover,
.star-input.filled {
  color: #f59e0b;
}

.star-input:hover ~ .star-input {
  color: #ddd;
}

.driver-name-link {
  color: var(--text-color);
  text-decoration: none;
}

.driver-name-link:hover {
  color: var(--primary-color);
}

.driver-name-link h3 {
  margin: 0;
}

.btn-sm {
  padding: 8px 12px;
  font-size: 13px;
}

@media (max-width: 992px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .status-row {
    flex-direction: column;
    gap: 16px;
  }
}
</style>
