<template>
  <div class="my-trips-page">
    <div class="page-header">
      <div class="container">
        <div class="d-flex justify-between align-center">
          <div>
            <h1 class="page-title">Trip Saya</h1>
            <p class="page-subtitle">Kelola tumpangan yang Anda tawarkan</p>
          </div>
          <router-link to="/trips/create" class="btn btn-primary">
            + Buat Trip Baru
          </router-link>
        </div>
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
      
      <div v-else-if="filteredTrips.length === 0" class="empty-state">
        <div class="empty-state-icon">🚗</div>
        <h3 class="empty-state-title">Belum Ada Trip</h3>
        <p class="empty-state-text">Anda belum membuat penawaran tumpangan.</p>
        <router-link to="/trips/create" class="btn btn-primary">Buat Trip Pertama</router-link>
      </div>
      
      <div v-else class="trips-list">
        <div v-for="trip in filteredTrips" :key="trip.id" class="trip-item card">
          <div class="card-body">
            <div class="trip-header">
              <div class="trip-route">
                <span class="trip-city">{{ trip.origin_city }}</span>
                <span class="trip-arrow">→</span>
                <span class="trip-city">{{ trip.destination_city }}</span>
              </div>
              <span class="badge" :class="getStatusClass(trip.status)">
                {{ getStatusLabel(trip.status) }}
              </span>
            </div>
            
            <div class="trip-info">
              <div class="trip-info-item">
                📅 {{ formatDate(trip.waktu_keberangkatan) }}
              </div>
              <div class="trip-info-item">
                🕐 {{ formatTime(trip.waktu_keberangkatan) }}
              </div>
              <div class="trip-info-item">
                💺 {{ trip.available_seats }}/{{ trip.max_passenger }} kursi
              </div>
              <div class="trip-info-item">
                👥 {{ trip.total_bookings || 0 }} pemesanan
              </div>
            </div>
            
            <div class="trip-price">
              {{ formatRupiah(trip.seat_price) }}/kursi
            </div>
            
            <div class="trip-actions">
              <router-link :to="`/trips/${trip.id}`" class="btn btn-outline btn-sm">
                Lihat Detail
              </router-link>
              
              <template v-if="trip.status === 'aktif'">
                <button @click="updateStatus(trip.id, 'dalam_perjalanan')" class="btn btn-primary btn-sm">
                  Mulai Perjalanan
                </button>
              </template>
              
              <template v-if="trip.status === 'dalam_perjalanan'">
                <button @click="updateStatus(trip.id, 'selesai')" class="btn btn-secondary btn-sm">
                  Selesai
                </button>
              </template>
              
              <template v-if="['menunggu', 'aktif'].includes(trip.status)">
                <button @click="updateStatus(trip.id, 'dibatalkan')" class="btn btn-danger btn-sm">
                  Batalkan
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'

const loading = ref(true)
const trips = ref([])
const activeTab = ref('all')

const tabs = [
  { label: 'Semua', value: 'all' },
  { label: 'Aktif', value: 'aktif' },
  { label: 'Dalam Perjalanan', value: 'dalam_perjalanan' },
  { label: 'Selesai', value: 'selesai' },
  { label: 'Dibatalkan', value: 'dibatalkan' }
]

const filteredTrips = computed(() => {
  let result = activeTab.value === 'all' 
    ? [...trips.value] 
    : trips.value.filter(t => t.status === activeTab.value)
  
  // Sort by created_at descending (newest first)
  return result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

onMounted(async () => {
  await fetchTrips()
})

async function fetchTrips() {
  loading.value = true
  try {
    const response = await api.get('/trips/my-trips')
    if (response.data.success) {
      trips.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch trips:', error)
  } finally {
    loading.value = false
  }
}

async function updateStatus(tripId, status) {
  if (!confirm(`Yakin ingin mengubah status trip menjadi "${getStatusLabel(status)}"?`)) {
    return
  }
  
  try {
    const response = await api.put(`/trips/${tripId}/status`, { status })
    if (response.data.success) {
      await fetchTrips()
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal mengupdate status')
  }
}

function getStatusClass(status) {
  const map = {
    'menunggu': 'badge-warning',
    'aktif': 'badge-success',
    'dalam_perjalanan': 'badge-info',
    'selesai': 'badge-primary',
    'dibatalkan': 'badge-danger'
  }
  return map[status] || 'badge-primary'
}

function getStatusLabel(status) {
  const map = {
    'menunggu': 'Menunggu',
    'aktif': 'Aktif',
    'dalam_perjalanan': 'Dalam Perjalanan',
    'selesai': 'Selesai',
    'dibatalkan': 'Dibatalkan'
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

.trips-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.trip-item {
  transition: box-shadow 0.2s;
}

.trip-item:hover {
  box-shadow: var(--shadow-md);
}

.trip-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.trip-route {
  display: flex;
  align-items: center;
  gap: 12px;
}

.trip-city {
  font-weight: 600;
  font-size: 18px;
}

.trip-arrow {
  color: var(--gray-color);
}

.trip-info {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 16px;
  color: var(--gray-color);
  font-size: 14px;
}

.trip-info-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.trip-price {
  font-size: 20px;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: 16px;
}

.trip-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  padding-top: 16px;
  border-top: 1px solid var(--border-color);
}

@media (max-width: 576px) {
  .trip-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
</style>
