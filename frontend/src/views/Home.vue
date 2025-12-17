<template>
  <div class="home">
    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <h1>Nebeng Bareng, Hemat Bersama</h1>
          <p>Platform berbagi tumpangan untuk perjalanan antar kota yang lebih hemat, ramah lingkungan, dan menyenangkan.</p>
          <div class="hero-buttons">
            <router-link to="/trips" class="btn btn-primary btn-lg">
              🔍 Cari Tumpangan
            </router-link>
            <router-link v-if="authStore.isDriver" to="/trips/create" class="btn btn-outline btn-lg">
              🚗 Tawarkan Tumpangan
            </router-link>
            <router-link v-else to="/become-driver" class="btn btn-outline btn-lg">
              🚗 Jadi Driver
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
      <div class="container">
        <div class="search-box">
          <h2>Mau pergi kemana?</h2>
          <form @submit.prevent="handleSearch" class="search-form">
            <div class="search-field">
              <label>Dari</label>
              <select v-model="searchForm.origin" class="form-control">
                <option value="">Pilih Kota Asal</option>
                <option v-for="city in cities" :key="city.id" :value="city.id">
                  {{ city.nama }}
                </option>
              </select>
            </div>
            <div class="search-field">
              <label>Ke</label>
              <select v-model="searchForm.destination" class="form-control">
                <option value="">Pilih Kota Tujuan</option>
                <option v-for="city in cities" :key="city.id" :value="city.id">
                  {{ city.nama }}
                </option>
              </select>
            </div>
            <div class="search-field">
              <label>Tanggal</label>
              <input type="date" v-model="searchForm.date" class="form-control" :min="today">
            </div>
            <div class="search-field">
              <label>Penumpang</label>
              <select v-model="searchForm.seats" class="form-control">
                <option value="1">1 Orang</option>
                <option value="2">2 Orang</option>
                <option value="3">3 Orang</option>
                <option value="4">4 Orang</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">
              Cari
            </button>
          </form>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features">
      <div class="container">
        <h2 class="section-title">Kenapa Nebeng?</h2>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">💰</div>
            <h3>Hemat Biaya</h3>
            <p>Berbagi biaya perjalanan dengan penumpang lain. Lebih murah dari transportasi umum!</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🌱</div>
            <h3>Ramah Lingkungan</h3>
            <p>Kurangi emisi karbon dengan berbagi tumpangan. Satu mobil, banyak penumpang.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🤝</div>
            <h3>Koneksi Baru</h3>
            <p>Bertemu orang baru dalam perjalanan. Siapa tahu ketemu jodoh atau partner bisnis!</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">✅</div>
            <h3>Aman & Terpercaya</h3>
            <p>Sistem rating dan review untuk driver dan penumpang. Perjalanan lebih aman.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
      <div class="container">
        <h2 class="section-title">Cara Kerja</h2>
        <div class="steps">
          <div class="step">
            <div class="step-number">1</div>
            <h3>Cari Tumpangan</h3>
            <p>Masukkan kota asal, tujuan, dan tanggal keberangkatan yang kamu inginkan.</p>
          </div>
          <div class="step">
            <div class="step-number">2</div>
            <h3>Pilih Driver</h3>
            <p>Pilih tumpangan dari driver yang sesuai dengan jadwal dan harga yang kamu mau.</p>
          </div>
          <div class="step">
            <div class="step-number">3</div>
            <h3>Booking & Bayar</h3>
            <p>Lakukan pemesanan dan bayar sesuai jumlah kursi yang kamu butuhkan.</p>
          </div>
          <div class="step">
            <div class="step-number">4</div>
            <h3>Nebeng!</h3>
            <p>Tunggu driver menjemput di titik jemput. Nikmati perjalananmu!</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Recent Trips -->
    <section class="recent-trips">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Tumpangan Terbaru</h2>
          <router-link to="/trips" class="btn btn-outline">Lihat Semua</router-link>
        </div>
        
        <div v-if="loading" class="loading-container">
          <div class="spinner"></div>
          <p>Memuat data...</p>
        </div>
        
        <div v-else-if="trips.length === 0" class="empty-state">
          <div class="empty-state-icon">🚗</div>
          <h3 class="empty-state-title">Belum Ada Tumpangan</h3>
          <p class="empty-state-text">Jadilah yang pertama menawarkan tumpangan!</p>
        </div>
        
        <div v-else class="trips-grid">
          <TripCard v-for="trip in trips" :key="trip.id" :trip="trip" />
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-content">
          <h2>Punya Mobil? Jadi Driver Sekarang!</h2>
          <p>Manfaatkan kursi kosong di mobilmu dan dapatkan penghasilan tambahan.</p>
          <router-link v-if="!authStore.isAuthenticated" to="/register" class="btn btn-primary btn-lg">
            Daftar Sebagai Driver
          </router-link>
          <router-link v-else-if="!authStore.isDriver" to="/become-driver" class="btn btn-primary btn-lg">
            Jadi Driver Sekarang
          </router-link>
          <router-link v-else to="/trips/create" class="btn btn-primary btn-lg">
            Tawarkan Tumpangan
          </router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../services/api'
import TripCard from '../components/TripCard.vue'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const trips = ref([])
const cities = ref([])

const searchForm = ref({
  origin: '',
  destination: '',
  date: '',
  seats: '1'
})

const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

onMounted(async () => {
  await Promise.all([fetchTrips(), fetchCities()])
})

async function fetchTrips() {
  try {
    const response = await api.get('/trips?limit=6')
    if (response.data.success) {
      trips.value = response.data.data.trips
    }
  } catch (error) {
    console.error('Failed to fetch trips:', error)
  } finally {
    loading.value = false
  }
}

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

function handleSearch() {
  const query = new URLSearchParams()
  if (searchForm.value.origin) query.set('origin', searchForm.value.origin)
  if (searchForm.value.destination) query.set('destination', searchForm.value.destination)
  if (searchForm.value.date) query.set('date', searchForm.value.date)
  if (searchForm.value.seats) query.set('seats', searchForm.value.seats)
  
  router.push({ path: '/trips', query: Object.fromEntries(query) })
}
</script>

<style scoped>
/* Hero */
.hero {
  background: linear-gradient(135deg, var(--primary-color) 0%, #7C3AED 100%);
  color: var(--white);
  padding: 100px 0;
  text-align: center;
}

.hero-content h1 {
  font-size: 48px;
  font-weight: 700;
  margin-bottom: 20px;
}

.hero-content p {
  font-size: 20px;
  opacity: 0.9;
  max-width: 600px;
  margin: 0 auto 40px;
}

.hero-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}

.hero .btn-primary {
  background: var(--white);
  color: var(--primary-color);
}

.hero .btn-primary:hover {
  background: rgba(255, 255, 255, 0.9);
  color: var(--primary-color);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.hero .btn-outline {
  border-color: var(--white);
  color: var(--white);
}

.hero .btn-outline:hover {
  background: var(--white);
  color: var(--primary-color);
}

/* Search Section */
.search-section {
  margin-top: -50px;
  padding-bottom: 60px;
}

.search-box {
  background: var(--white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  padding: 40px;
}

.search-box h2 {
  text-align: center;
  margin-bottom: 30px;
}

.search-form {
  display: flex;
  gap: 16px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.search-field {
  flex: 1;
  min-width: 150px;
}

.search-field label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  font-size: 14px;
}

.search-form .btn {
  height: 48px;
}

/* Features */
.features {
  padding: 80px 0;
  background: var(--white);
}

.section-title {
  text-align: center;
  font-size: 32px;
  margin-bottom: 50px;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 30px;
}

.feature-card {
  text-align: center;
  padding: 30px;
}

.feature-icon {
  font-size: 48px;
  margin-bottom: 20px;
}

.feature-card h3 {
  margin-bottom: 12px;
}

.feature-card p {
  color: var(--gray-color);
  font-size: 14px;
}

/* How It Works */
.how-it-works {
  padding: 80px 0;
}

.steps {
  display: flex;
  gap: 40px;
  justify-content: center;
}

.step {
  text-align: center;
  max-width: 250px;
}

.step-number {
  width: 60px;
  height: 60px;
  background: var(--primary-color);
  color: var(--white);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: 700;
  margin: 0 auto 20px;
}

.step h3 {
  margin-bottom: 12px;
}

.step p {
  color: var(--gray-color);
  font-size: 14px;
}

/* Recent Trips */
.recent-trips {
  padding: 80px 0;
  background: var(--white);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
}

.section-header .section-title {
  margin-bottom: 0;
}

.trips-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

/* CTA Section */
.cta-section {
  padding: 100px 0;
  background: linear-gradient(135deg, var(--secondary-color) 0%, #0D9488 100%);
  color: var(--white);
  text-align: center;
}

.cta-content h2 {
  font-size: 36px;
  margin-bottom: 16px;
}

.cta-content p {
  font-size: 18px;
  opacity: 0.9;
  margin-bottom: 30px;
}

.cta-section .btn-primary {
  background: var(--white);
  color: var(--secondary-color);
}

.cta-section .btn-primary:hover {
  background: rgba(255, 255, 255, 0.95);
  color: var(--secondary-color);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Responsive */
@media (max-width: 992px) {
  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .trips-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .steps {
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .hero-content h1 {
    font-size: 32px;
  }
  
  .search-form {
    flex-direction: column;
  }
  
  .search-field {
    width: 100%;
  }
  
  .features-grid,
  .trips-grid {
    grid-template-columns: 1fr;
  }
}
</style>
