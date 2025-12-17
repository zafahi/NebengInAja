<template>
  <div class="profile-page">
    <div class="page-header">
      <div class="container">
        <h1 class="page-title">Profil Saya</h1>
        <p class="page-subtitle">Kelola informasi akun Anda</p>
      </div>
    </div>
    
    <div class="container">
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
      </div>
      
      <div v-else class="profile-grid">
        <div class="profile-sidebar">
          <div class="card profile-card">
            <div class="card-body text-center">
              <img :src="avatarUrl" :alt="user.nama" class="profile-avatar">
              <h2 class="profile-name">{{ user.nama }}</h2>
              <p class="profile-role">{{ roleLabel }}</p>
              
              <div v-if="user.driver_id" class="profile-stats">
                <div class="stat-item">
                  <span class="stat-value">⭐ {{ user.rating || '0.00' }}</span>
                  <span class="stat-label">Rating</span>
                </div>
                <div class="stat-item">
                  <span class="stat-value">{{ user.total_trips || 0 }}</span>
                  <span class="stat-label">Trips</span>
                </div>
              </div>
              
              <div v-if="user.driver_id && user.is_verified" class="verified-badge">
                ✓ Driver Terverifikasi
              </div>
            </div>
          </div>
          
          <div class="profile-menu">
            <router-link v-if="authStore.isDriver" to="/my-trips" class="menu-item">
              🚗 Trip Saya
            </router-link>
            <router-link to="/my-bookings" class="menu-item">
              📋 Pemesanan Saya
            </router-link>
            <router-link v-if="authStore.isDriver" to="/my-vehicles" class="menu-item">
              🚙 Kendaraan Saya
            </router-link>
            <router-link v-if="!authStore.isDriver" to="/become-driver" class="menu-item">
              🎯 Jadi Driver
            </router-link>
          </div>
        </div>
        
        <div class="profile-main">
          <div class="card">
            <div class="card-header">
              <h2>Informasi Pribadi</h2>
            </div>
            <div class="card-body">
              <form @submit.prevent="handleUpdate">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <div v-if="success" class="alert alert-success">{{ success }}</div>
                
                <div class="form-group">
                  <label class="form-label">Nama Lengkap</label>
                  <input 
                    type="text" 
                    v-model="form.nama" 
                    class="form-control"
                    required
                  >
                </div>
                
                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input 
                    type="email" 
                    :value="user.email" 
                    class="form-control"
                    disabled
                  >
                  <small class="text-gray">Email tidak dapat diubah</small>
                </div>
                
                <div class="form-group">
                  <label class="form-label">Nomor HP</label>
                  <input 
                    type="tel" 
                    v-model="form.nomor_hp" 
                    class="form-control"
                    required
                  >
                </div>
                
                <div class="form-group">
                  <label class="form-label">Alamat</label>
                  <textarea 
                    v-model="form.alamat" 
                    class="form-control"
                    rows="3"
                    placeholder="Alamat lengkap Anda"
                  ></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" :disabled="updating">
                  {{ updating ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
              </form>
            </div>
          </div>
          
          <div v-if="user.driver_id" class="card">
            <div class="card-header">
              <h2>Informasi Driver</h2>
            </div>
            <div class="card-body">
              <div class="info-row">
                <span class="info-label">Nomor SIM</span>
                <span class="info-value">{{ user.no_sim }}</span>
              </div>
              <div class="info-row">
                <span class="info-label">Status Verifikasi</span>
                <span class="info-value">
                  <span v-if="user.is_verified" class="badge badge-success">Terverifikasi</span>
                  <span v-else class="badge badge-warning">Belum Terverifikasi</span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-label">Total Perjalanan</span>
                <span class="info-value">{{ user.total_trips || 0 }} trip</span>
              </div>
              <div class="info-row">
                <span class="info-label">Rating</span>
                <span class="info-value">⭐ {{ user.rating || '0.00' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const authStore = useAuthStore()

const loading = ref(true)
const updating = ref(false)
const error = ref('')
const success = ref('')
const user = ref({})

const form = ref({
  nama: '',
  nomor_hp: '',
  alamat: ''
})

const avatarUrl = computed(() => {
  return user.value.foto_profil && user.value.foto_profil !== 'default.png'
    ? `/uploads/${user.value.foto_profil}`
    : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.value.nama || 'User') + '&background=4F46E5&color=fff&size=128'
})

const roleLabel = computed(() => {
  const map = {
    'penumpang': 'Penumpang',
    'driver': 'Driver',
    'both': 'Driver & Penumpang'
  }
  return map[user.value.role] || 'Penumpang'
})

onMounted(async () => {
  await fetchProfile()
})

async function fetchProfile() {
  try {
    const response = await api.get('/users/me')
    if (response.data.success) {
      user.value = response.data.data
      form.value = {
        nama: user.value.nama,
        nomor_hp: user.value.nomor_hp,
        alamat: user.value.alamat || ''
      }
    }
  } catch (err) {
    console.error('Failed to fetch profile:', err)
  } finally {
    loading.value = false
  }
}

async function handleUpdate() {
  updating.value = true
  error.value = ''
  success.value = ''
  
  try {
    const response = await api.put('/users/me', form.value)
    if (response.data.success) {
      success.value = 'Profil berhasil diperbarui'
      user.value = response.data.data
      await authStore.fetchProfile()
    } else {
      error.value = response.data.message
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memperbarui profil'
  } finally {
    updating.value = false
  }
}
</script>

<style scoped>
.profile-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 30px;
}

.profile-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.profile-main {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.profile-card {
  text-align: center;
}

.profile-avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 16px;
  border: 4px solid var(--light-gray);
}

.profile-name {
  font-size: 24px;
  margin-bottom: 4px;
}

.profile-role {
  color: var(--gray-color);
  margin-bottom: 20px;
}

.profile-stats {
  display: flex;
  justify-content: center;
  gap: 30px;
  padding: 16px 0;
  border-top: 1px solid var(--border-color);
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 16px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: 600;
}

.stat-label {
  font-size: 12px;
  color: var(--gray-color);
}

.verified-badge {
  background: rgba(16, 185, 129, 0.1);
  color: var(--secondary-color);
  padding: 8px 16px;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 500;
}

.profile-menu {
  background: var(--white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.menu-item {
  display: block;
  padding: 16px 20px;
  color: var(--dark-color);
  border-bottom: 1px solid var(--border-color);
  transition: background 0.2s;
}

.menu-item:last-child {
  border-bottom: none;
}

.menu-item:hover {
  background: var(--light-gray);
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  color: var(--gray-color);
}

.info-value {
  font-weight: 500;
}

@media (max-width: 768px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
}
</style>
