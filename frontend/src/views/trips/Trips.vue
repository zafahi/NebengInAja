<template>
  <div class="trips-page">
    <div class="page-header">
      <div class="container">
        <h1 class="page-title">Cari Tumpangan</h1>
        <p class="page-subtitle">Temukan tumpangan yang sesuai dengan tujuanmu</p>
      </div>
    </div>
    
    <div class="container">
      <!-- Search/Filter -->
      <div class="filter-card">
        <form @submit.prevent="searchTrips" class="filter-form">
          <div class="filter-group">
            <label>Dari</label>
            <select v-model="filters.origin" class="form-control">
              <option value="">Semua Kota</option>
              <option v-for="city in cities" :key="city.id" :value="city.id">
                {{ city.nama }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Ke</label>
            <select v-model="filters.destination" class="form-control">
              <option value="">Semua Kota</option>
              <option v-for="city in cities" :key="city.id" :value="city.id">
                {{ city.nama }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Tanggal</label>
            <input type="date" v-model="filters.date" class="form-control" :min="today">
          </div>
          
          <div class="filter-group">
            <label>Jumlah Kursi</label>
            <select v-model="filters.seats" class="form-control">
              <option value="">Semua</option>
              <option value="1">Min. 1 Kursi</option>
              <option value="2">Min. 2 Kursi</option>
              <option value="3">Min. 3 Kursi</option>
              <option value="4">Min. 4 Kursi</option>
            </select>
          </div>
          
          <button type="submit" class="btn btn-primary">
            🔍 Cari
          </button>
          
          <button type="button" @click="resetFilters" class="btn btn-outline">
            Reset
          </button>
        </form>
      </div>
      
      <!-- Results -->
      <div class="results-info" v-if="!loading">
        <span>Ditemukan {{ trips.length }} tumpangan</span>
      </div>
      
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
        <p>Mencari tumpangan...</p>
      </div>
      
      <div v-else-if="trips.length === 0" class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <h3 class="empty-state-title">Tidak Ada Tumpangan</h3>
        <p class="empty-state-text">Coba ubah filter pencarian atau cek lagi nanti.</p>
      </div>
      
      <div v-else class="trips-list">
        <TripCard v-for="trip in trips" :key="trip.id" :trip="trip" />
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.total_pages > 1" class="pagination">
        <button 
          @click="changePage(pagination.page - 1)" 
          :disabled="pagination.page === 1"
          class="btn btn-outline btn-sm"
        >
          ← Sebelumnya
        </button>
        
        <span class="pagination-info">
          Halaman {{ pagination.page }} dari {{ pagination.total_pages }}
        </span>
        
        <button 
          @click="changePage(pagination.page + 1)" 
          :disabled="pagination.page === pagination.total_pages"
          class="btn btn-outline btn-sm"
        >
          Selanjutnya →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../services/api'
import TripCard from '../../components/TripCard.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const trips = ref([])
const cities = ref([])
const pagination = ref({
  page: 1,
  limit: 10,
  total: 0,
  total_pages: 1
})

const filters = ref({
  origin: '',
  destination: '',
  date: '',
  seats: ''
})

const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

// Initialize filters from URL
onMounted(async () => {
  filters.value.origin = route.query.origin || ''
  filters.value.destination = route.query.destination || ''
  filters.value.date = route.query.date || ''
  filters.value.seats = route.query.seats || ''
  
  await Promise.all([fetchCities(), fetchTrips()])
})

// Watch route changes
watch(() => route.query, () => {
  filters.value.origin = route.query.origin || ''
  filters.value.destination = route.query.destination || ''
  filters.value.date = route.query.date || ''
  filters.value.seats = route.query.seats || ''
  fetchTrips()
})

async function fetchCities() {
  try {
    const response = await api.get('/cities')
    if (response.data.success) {
      cities.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch cities:', error)
  }
}

async function fetchTrips() {
  loading.value = true
  
  try {
    const params = new URLSearchParams()
    
    if (filters.value.origin || filters.value.destination || filters.value.date || filters.value.seats) {
      // Use search endpoint
      if (filters.value.origin) params.set('origin', filters.value.origin)
      if (filters.value.destination) params.set('destination', filters.value.destination)
      if (filters.value.date) params.set('date', filters.value.date)
      if (filters.value.seats) params.set('seats', filters.value.seats)
      
      const response = await api.get('/trips/search?' + params.toString())
      if (response.data.success) {
        trips.value = response.data.data
        pagination.value = { page: 1, total_pages: 1, total: trips.value.length }
      }
    } else {
      // Get all trips
      params.set('page', pagination.value.page)
      params.set('limit', pagination.value.limit)
      
      const response = await api.get('/trips?' + params.toString())
      if (response.data.success) {
        trips.value = response.data.data.trips
        pagination.value = response.data.data.pagination
      }
    }
  } catch (error) {
    console.error('Failed to fetch trips:', error)
  } finally {
    loading.value = false
  }
}

function searchTrips() {
  const query = {}
  if (filters.value.origin) query.origin = filters.value.origin
  if (filters.value.destination) query.destination = filters.value.destination
  if (filters.value.date) query.date = filters.value.date
  if (filters.value.seats) query.seats = filters.value.seats
  
  router.push({ path: '/trips', query })
}

function resetFilters() {
  filters.value = { origin: '', destination: '', date: '', seats: '' }
  router.push('/trips')
}

function changePage(page) {
  pagination.value.page = page
  fetchTrips()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<style scoped>
.filter-card {
  background: var(--white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  padding: 24px;
  margin-bottom: 30px;
}

.filter-form {
  display: flex;
  gap: 16px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-group {
  flex: 1;
  min-width: 150px;
}

.filter-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  font-size: 14px;
}

.results-info {
  margin-bottom: 20px;
  color: var(--gray-color);
}

.trips-list {
  display: grid;
  gap: 20px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-top: 40px;
}

.pagination-info {
  color: var(--gray-color);
}

@media (max-width: 768px) {
  .filter-form {
    flex-direction: column;
  }
  
  .filter-group {
    width: 100%;
  }
}
</style>
