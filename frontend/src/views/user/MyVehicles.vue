<template>
  <div class="my-vehicles-page">
    <div class="page-header">
      <div class="container">
        <div class="d-flex justify-between align-center">
          <div>
            <h1 class="page-title">Kendaraan Saya</h1>
            <p class="page-subtitle">Kelola daftar kendaraan Anda</p>
          </div>
          <button @click="showAddModal = true" class="btn btn-primary">
            + Tambah Kendaraan
          </button>
        </div>
      </div>
    </div>
    
    <div class="container">
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
      </div>
      
      <div v-else-if="vehicles.length === 0" class="empty-state">
        <div class="empty-state-icon">🚗</div>
        <h3 class="empty-state-title">Belum Ada Kendaraan</h3>
        <p class="empty-state-text">Tambahkan kendaraan Anda untuk mulai menawarkan tumpangan.</p>
        <button @click="showAddModal = true" class="btn btn-primary">
          Tambah Kendaraan
        </button>
      </div>
      
      <div v-else class="vehicles-grid">
        <div v-for="vehicle in vehicles" :key="vehicle.id" class="vehicle-card card">
          <div class="card-body">
            <div class="vehicle-icon">🚗</div>
            <div class="vehicle-details">
              <h3 class="vehicle-name">{{ vehicle.brand_nama }} {{ vehicle.model_nama }}</h3>
              <div class="vehicle-plate">{{ vehicle.plate_number }}</div>
              <div class="vehicle-info">
                <span>{{ vehicle.warna || '-' }}</span>
                <span>•</span>
                <span>{{ vehicle.tahun || '-' }}</span>
                <span>•</span>
                <span>{{ vehicle.kapasitas }} kursi</span>
              </div>
            </div>
            <div class="vehicle-actions">
              <button @click="editVehicle(vehicle)" class="btn btn-outline btn-sm">
                Edit
              </button>
              <button @click="deleteVehicle(vehicle.id)" class="btn btn-danger btn-sm">
                Hapus
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <h2>{{ editingVehicle ? 'Edit Kendaraan' : 'Tambah Kendaraan' }}</h2>
        
        <form @submit.prevent="handleSubmit">
          <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
          
          <div class="form-group">
            <label class="form-label">Merk Kendaraan</label>
            <select v-model="form.brand_id" class="form-control" required @change="onBrandChange">
              <option value="">Pilih Merk</option>
              <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                {{ brand.nama }}
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">Model</label>
            <select v-model="form.model_id" class="form-control" required :disabled="!form.brand_id">
              <option value="">Pilih Model</option>
              <option v-for="model in models" :key="model.id" :value="model.id">
                {{ model.nama }} ({{ model.kapasitas }} kursi)
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">Nomor Plat</label>
            <input 
              type="text" 
              v-model="form.plate_number" 
              class="form-control"
              placeholder="Contoh: B 1234 ABC"
              required
            >
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Warna</label>
              <input 
                type="text" 
                v-model="form.warna" 
                class="form-control"
                placeholder="Contoh: Putih"
              >
            </div>
            
            <div class="form-group">
              <label class="form-label">Tahun</label>
              <input 
                type="number" 
                v-model="form.tahun" 
                class="form-control"
                placeholder="Contoh: 2022"
                min="1990"
                :max="new Date().getFullYear() + 1"
              >
            </div>
          </div>
          
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn btn-outline">
              Batal
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Menyimpan...' : (editingVehicle ? 'Update' : 'Simpan') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const loading = ref(true)
const vehicles = ref([])
const brands = ref([])
const models = ref([])

const showAddModal = ref(false)
const editingVehicle = ref(null)
const submitting = ref(false)
const formError = ref('')

const form = ref({
  brand_id: '',
  model_id: '',
  plate_number: '',
  warna: '',
  tahun: ''
})

onMounted(async () => {
  await Promise.all([fetchVehicles(), fetchBrands()])
})

async function fetchVehicles() {
  loading.value = true
  try {
    const response = await api.get('/vehicles/my-vehicles')
    if (response.data.success) {
      vehicles.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch vehicles:', error)
  } finally {
    loading.value = false
  }
}

async function fetchBrands() {
  try {
    const response = await api.get('/brands')
    if (response.data.success) {
      brands.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch brands:', error)
  }
}

async function fetchModels(brandId) {
  try {
    const response = await api.get(`/brands/models/${brandId}`)
    if (response.data.success) {
      models.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch models:', error)
  }
}

function onBrandChange() {
  form.value.model_id = ''
  if (form.value.brand_id) {
    fetchModels(form.value.brand_id)
  } else {
    models.value = []
  }
}

function editVehicle(vehicle) {
  editingVehicle.value = vehicle
  form.value = {
    brand_id: vehicle.brand_id,
    model_id: vehicle.model_id,
    plate_number: vehicle.plate_number,
    warna: vehicle.warna || '',
    tahun: vehicle.tahun || ''
  }
  fetchModels(vehicle.brand_id)
  showAddModal.value = true
}

function closeModal() {
  showAddModal.value = false
  editingVehicle.value = null
  formError.value = ''
  form.value = {
    brand_id: '',
    model_id: '',
    plate_number: '',
    warna: '',
    tahun: ''
  }
  models.value = []
}

async function handleSubmit() {
  submitting.value = true
  formError.value = ''
  
  try {
    let response
    if (editingVehicle.value) {
      response = await api.put(`/vehicles/${editingVehicle.value.id}`, form.value)
    } else {
      response = await api.post('/vehicles', form.value)
    }
    
    if (response.data.success) {
      closeModal()
      await fetchVehicles()
    } else {
      formError.value = response.data.message
    }
  } catch (error) {
    formError.value = error.response?.data?.message || 'Gagal menyimpan kendaraan'
  } finally {
    submitting.value = false
  }
}

async function deleteVehicle(vehicleId) {
  if (!confirm('Yakin ingin menghapus kendaraan ini?')) return
  
  try {
    const response = await api.delete(`/vehicles/${vehicleId}`)
    if (response.data.success) {
      await fetchVehicles()
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Gagal menghapus kendaraan')
  }
}
</script>

<style scoped>
.vehicles-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.vehicle-card .card-body {
  display: flex;
  align-items: center;
  gap: 20px;
}

.vehicle-icon {
  font-size: 48px;
}

.vehicle-details {
  flex: 1;
}

.vehicle-name {
  font-size: 18px;
  margin-bottom: 4px;
}

.vehicle-plate {
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 4px;
}

.vehicle-info {
  font-size: 14px;
  color: var(--gray-color);
  display: flex;
  gap: 8px;
}

.vehicle-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

@media (max-width: 768px) {
  .vehicles-grid {
    grid-template-columns: 1fr;
  }
  
  .vehicle-card .card-body {
    flex-direction: column;
    text-align: center;
  }
  
  .vehicle-actions {
    flex-direction: row;
  }
}
</style>
