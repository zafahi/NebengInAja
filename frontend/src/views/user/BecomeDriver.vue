<template>
  <div class="become-driver-page">
    <div class="page-header">
      <div class="container">
        <h1 class="page-title">Jadi Driver</h1>
        <p class="page-subtitle">Daftar sebagai driver dan mulai berbagi tumpangan</p>
      </div>
    </div>
    
    <div class="container">
      <div class="become-driver-content">
        <div class="benefits-section">
          <h2>Keuntungan Menjadi Driver</h2>
          <div class="benefits-grid">
            <div class="benefit-item">
              <div class="benefit-icon">💰</div>
              <h3>Penghasilan Tambahan</h3>
              <p>Dapatkan uang dari kursi kosong di mobil Anda selama perjalanan.</p>
            </div>
            <div class="benefit-item">
              <div class="benefit-icon">🤝</div>
              <h3>Bertemu Orang Baru</h3>
              <p>Kenalan dengan orang-orang baru dan perluas jaringan Anda.</p>
            </div>
            <div class="benefit-item">
              <div class="benefit-icon">🌱</div>
              <h3>Ramah Lingkungan</h3>
              <p>Berkontribusi mengurangi polusi dengan berbagi kendaraan.</p>
            </div>
            <div class="benefit-item">
              <div class="benefit-icon">⏰</div>
              <h3>Fleksibel</h3>
              <p>Tentukan sendiri jadwal dan rute perjalanan Anda.</p>
            </div>
          </div>
        </div>
        
        <div class="register-section">
          <div class="card">
            <div class="card-header">
              <h2>Daftar Sebagai Driver</h2>
            </div>
            <div class="card-body">
              <div v-if="authStore.isDriver" class="already-driver">
                <div class="success-icon">✅</div>
                <h3>Anda Sudah Terdaftar Sebagai Driver!</h3>
                <p>Mulai tawarkan tumpangan dan dapatkan penghasilan tambahan.</p>
                <div class="action-buttons">
                  <router-link to="/my-vehicles" class="btn btn-outline">
                    Kelola Kendaraan
                  </router-link>
                  <router-link to="/trips/create" class="btn btn-primary">
                    Buat Tumpangan
                  </router-link>
                </div>
              </div>
              
              <form v-else @submit.prevent="handleSubmit">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <div v-if="success" class="alert alert-success">{{ success }}</div>
                
                <div class="form-group">
                  <label class="form-label">Nomor SIM</label>
                  <input 
                    type="text" 
                    v-model="form.no_sim" 
                    class="form-control"
                    placeholder="Masukkan nomor SIM Anda"
                    required
                  >
                  <small class="text-gray">Pastikan SIM masih berlaku</small>
                </div>
                
                <div class="requirements">
                  <h4>Persyaratan:</h4>
                  <ul>
                    <li>✓ Memiliki SIM yang masih berlaku</li>
                    <li>✓ Memiliki kendaraan pribadi</li>
                    <li>✓ Kendaraan dalam kondisi baik</li>
                    <li>✓ Berusia minimal 18 tahun</li>
                  </ul>
                </div>
                
                <div class="terms">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="agreeTerms" required>
                    <span>Saya setuju dengan <a href="#">Syarat & Ketentuan</a> sebagai driver Nebeng</span>
                  </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="loading || !agreeTerms">
                  {{ loading ? 'Memproses...' : 'Daftar Sebagai Driver' }}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const error = ref('')
const success = ref('')
const agreeTerms = ref(false)

const form = ref({
  no_sim: ''
})

async function handleSubmit() {
  loading.value = true
  error.value = ''
  success.value = ''
  
  try {
    const response = await api.put(`/users/${authStore.user.id}/become-driver`, form.value)
    
    if (response.data.success) {
      success.value = 'Selamat! Anda berhasil terdaftar sebagai driver.'
      await authStore.fetchProfile()
      
      setTimeout(() => {
        router.push('/my-vehicles')
      }, 2000)
    } else {
      error.value = response.data.message
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal mendaftar sebagai driver'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.become-driver-content {
  max-width: 900px;
  margin: 0 auto;
}

.benefits-section {
  margin-bottom: 40px;
}

.benefits-section h2 {
  text-align: center;
  margin-bottom: 30px;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.benefit-item {
  text-align: center;
  padding: 24px;
  background: var(--white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
}

.benefit-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.benefit-item h3 {
  font-size: 16px;
  margin-bottom: 8px;
}

.benefit-item p {
  font-size: 14px;
  color: var(--gray-color);
}

.register-section .card {
  max-width: 500px;
  margin: 0 auto;
}

.already-driver {
  text-align: center;
  padding: 20px;
}

.success-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.already-driver h3 {
  margin-bottom: 8px;
}

.already-driver p {
  color: var(--gray-color);
  margin-bottom: 24px;
}

.action-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
}

.requirements {
  background: var(--light-gray);
  padding: 20px;
  border-radius: var(--radius);
  margin-bottom: 20px;
}

.requirements h4 {
  margin-bottom: 12px;
}

.requirements ul {
  list-style: none;
}

.requirements li {
  margin-bottom: 8px;
  font-size: 14px;
}

.terms {
  margin-bottom: 24px;
}

.checkbox-label {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  cursor: pointer;
}

.checkbox-label input {
  margin-top: 4px;
}

.checkbox-label span {
  font-size: 14px;
  color: var(--gray-color);
}

@media (max-width: 768px) {
  .benefits-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 576px) {
  .benefits-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>
