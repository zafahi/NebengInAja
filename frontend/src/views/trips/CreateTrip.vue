<template>
  <div class="create-trip-page">
    <div class="page-header">
      <div class="container">
        <h1 class="page-title">Tawarkan Tumpangan</h1>
        <p class="page-subtitle">Buat penawaran tumpangan untuk perjalanan Anda</p>
      </div>
    </div>
    
    <div class="container">
      <div class="form-card">
        <form @submit.prevent="handleSubmit">
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="success" class="alert alert-success">{{ success }}</div>
          
          <h2 class="form-section-title">Rute Perjalanan</h2>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Kota Asal *</label>
              <select v-model="form.origin_city_id" class="form-control" required>
                <option value="">Pilih Kota Asal</option>
                <option v-for="city in cities" :key="city.id" :value="city.id">
                  {{ city.nama }}
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">Kota Tujuan *</label>
              <select v-model="form.destination_city_id" class="form-control" required>
                <option value="">Pilih Kota Tujuan</option>
                <option v-for="city in cities" :key="city.id" :value="city.id">
                  {{ city.nama }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Titik Jemput *</label>
            <input 
              type="text" 
              v-model="form.titik_jemput" 
              class="form-control"
              placeholder="Contoh: Terminal Pulo Gebang, Jakarta Timur"
              required
            >
          </div>
          
          <div class="form-group">
            <label class="form-label">Titik Tujuan *</label>
            <input 
              type="text" 
              v-model="form.titik_tujuan" 
              class="form-control"
              placeholder="Contoh: Terminal Leuwi Panjang, Bandung"
              required
            >
          </div>
          
          <h2 class="form-section-title">Waktu Keberangkatan</h2>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Tanggal *</label>
              <input 
                type="date" 
                v-model="form.date" 
                class="form-control"
                :min="today"
                required
              >
            </div>
            
            <div class="form-group">
              <label class="form-label">Jam *</label>
              <input 
                type="time" 
                v-model="form.time" 
                class="form-control"
                required
              >
            </div>
          </div>
          
          <h2 class="form-section-title">Kendaraan & Harga</h2>
          
          <div class="form-group">
            <label class="form-label">Pilih Kendaraan *</label>
            <select v-model="form.vehicle_id" class="form-control" required @change="onVehicleChange">
              <option value="">Pilih Kendaraan</option>
              <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                {{ vehicle.brand_nama }} {{ vehicle.model_nama }} - {{ vehicle.plate_number }}
              </option>
            </select>
            <router-link v-if="vehicles.length === 0" to="/my-vehicles" class="text-sm text-primary mt-1">
              + Tambah kendaraan terlebih dahulu
            </router-link>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Jumlah Kursi Tersedia *</label>
              <select v-model="form.max_passenger" class="form-control" required>
                <option value="">Pilih Jumlah</option>
                <option v-for="n in maxSeats" :key="n" :value="n">{{ n }} Kursi</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">Harga per Kursi (Rp) *</label>
              <input 
                type="number" 
                v-model="form.seat_price" 
                class="form-control"
                placeholder="Contoh: 75000"
                min="1000"
                required
              >
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Deskripsi/Keterangan</label>
            <textarea 
              v-model="form.deskripsi" 
              class="form-control"
              placeholder="Informasi tambahan: fasilitas, aturan, dll."
              rows="4"
            ></textarea>
          </div>
          
          <div class="form-actions">
            <button type="button" @click="$router.back()" class="btn btn-outline">
              Batal
            </button>
            <button type="submit" class="btn btn-primary btn-lg" :disabled="loading">
              {{ loading ? 'Memproses...' : 'Buat Tumpangan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

const router = useRouter()

const loading = ref(false)
const error = ref('')
const success = ref('')
const cities = ref([])
const vehicles = ref([])

const form = ref({
  origin_city_id: '',
  destination_city_id: '',
  titik_jemput: '',
  titik_tujuan: '',
  date: '',
  time: '',
  vehicle_id: '',
  max_passenger: '',
  seat_price: '',
  deskripsi: ''
})

const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const selectedVehicle = computed(() => {
  return vehicles.value.find(v => v.id == form.value.vehicle_id)
})

const maxSeats = computed(() => {
  if (selectedVehicle.value) {
    return selectedVehicle.value.kapasitas - 1 // Minus driver seat
  }
  return 6
})

onMounted(async () => {
  await Promise.all([fetchCities(), fetchVehicles()])
})

async function fetchCities() {
  try {
    const response = await api.get('/cities')
    if (response.data.success) {
      cities.value = response.data.data
    }
  } catch (err) {
    console.error('Failed to fetch cities:', err)
  }
}

async function fetchVehicles() {
  try {
    const response = await api.get('/vehicles/my-vehicles')
    if (response.data.success) {
      vehicles.value = response.data.data
    }
  } catch (err) {
    console.error('Failed to fetch vehicles:', err)
  }
}

function onVehicleChange() {
  // Reset max_passenger when vehicle changes
  form.value.max_passenger = ''
}

async function handleSubmit() {
  error.value = ''
  success.value = ''
  
  // Validation
  if (form.value.origin_city_id === form.value.destination_city_id) {
    error.value = 'Kota asal dan tujuan tidak boleh sama'
    return
  }
  
  loading.value = true
  
  try {
    const waktu_keberangkatan = `${form.value.date} ${form.value.time}:00`
    
    const response = await api.post('/trips', {
      vehicle_id: form.value.vehicle_id,
      origin_city_id: form.value.origin_city_id,
      destination_city_id: form.value.destination_city_id,
      titik_jemput: form.value.titik_jemput,
      titik_tujuan: form.value.titik_tujuan,
      waktu_keberangkatan: waktu_keberangkatan,
      seat_price: parseInt(form.value.seat_price),
      max_passenger: parseInt(form.value.max_passenger),
      deskripsi: form.value.deskripsi
    })
    
    if (response.data.success) {
      success.value = response.data.message
      setTimeout(() => {
        router.push('/my-trips')
      }, 2000)
    } else {
      error.value = response.data.message
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal membuat tumpangan'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.form-card {
  background: var(--white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  padding: 40px;
  max-width: 700px;
  margin: 0 auto;
}

.form-section-title {
  font-size: 18px;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid var(--primary-color);
}

.form-section-title:not(:first-child) {
  margin-top: 30px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-actions {
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  margin-top: 30px;
  padding-top: 30px;
  border-top: 1px solid var(--border-color);
}

@media (max-width: 576px) {
  .form-card {
    padding: 24px;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .form-actions .btn {
    width: 100%;
  }
}
</style>
