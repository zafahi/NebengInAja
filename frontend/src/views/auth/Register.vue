<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <div class="auth-header">
          <router-link to="/" class="auth-logo">🚗 Nebeng</router-link>
          <h1>Buat Akun Baru</h1>
          <p>Daftar untuk mulai menggunakan Nebeng</p>
        </div>
        
        <form @submit.prevent="handleRegister" class="auth-form">
          <div v-if="authStore.error" class="alert alert-danger">
            {{ authStore.error }}
          </div>
          
          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input 
              type="text" 
              v-model="form.nama" 
              class="form-control" 
              placeholder="Masukkan nama lengkap"
              required
            >
          </div>
          
          <div class="form-group">
            <label class="form-label">Email</label>
            <input 
              type="email" 
              v-model="form.email" 
              class="form-control" 
              placeholder="nama@email.com"
              required
            >
          </div>
          
          <div class="form-group">
            <label class="form-label">Nomor HP</label>
            <input 
              type="tel" 
              v-model="form.nomor_hp" 
              class="form-control" 
              placeholder="08xxxxxxxxxx"
              required
            >
          </div>
          
          <div class="form-group">
            <label class="form-label">Password</label>
            <input 
              type="password" 
              v-model="form.password" 
              class="form-control" 
              placeholder="Minimal 6 karakter"
              required
              minlength="6"
            >
          </div>
          
          <div class="form-group">
            <label class="form-label">Konfirmasi Password</label>
            <input 
              type="password" 
              v-model="form.confirmPassword" 
              class="form-control" 
              placeholder="Ulangi password"
              required
            >
          </div>
          
          <div class="form-group">
            <label class="form-label">Daftar sebagai</label>
            <div class="role-cards">
              <div 
                class="role-card" 
                :class="{ active: form.role === 'penumpang' }"
                @click="form.role = 'penumpang'"
              >
                <div class="role-icon">🧑‍💼</div>
                <div class="role-name">Penumpang</div>
                <div class="role-desc">Cari tumpangan untuk perjalananmu</div>
              </div>
              <div 
                class="role-card" 
                :class="{ active: form.role === 'driver' }"
                @click="form.role = 'driver'"
              >
                <div class="role-icon">🚗</div>
                <div class="role-name">Driver</div>
                <div class="role-desc">Tawarkan tumpangan & dapatkan uang</div>
              </div>
              <div 
                class="role-card" 
                :class="{ active: form.role === 'both' }"
                @click="form.role = 'both'"
              >
                <div class="role-icon">🔄</div>
                <div class="role-name">Keduanya</div>
                <div class="role-desc">Fleksibel sebagai driver atau penumpang</div>
              </div>
            </div>
          </div>
          
          <div v-if="form.role === 'driver' || form.role === 'both'" class="form-group">
            <label class="form-label">Nomor SIM</label>
            <input 
              type="text" 
              v-model="form.no_sim" 
              class="form-control" 
              placeholder="Masukkan nomor SIM"
              required
            >
          </div>
          
          <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="authStore.loading">
            {{ authStore.loading ? 'Memproses...' : 'Daftar' }}
          </button>
        </form>
        
        <div class="auth-footer">
          <p>Sudah punya akun? <router-link to="/login">Login</router-link></p>
        </div>
      </div>
      
      <div class="auth-info">
        <h2>Bergabung dengan Nebeng</h2>
        <p>Jadilah bagian dari komunitas berbagi tumpangan terbesar di Indonesia.</p>
        <ul class="auth-features">
          <li>✅ Gratis tanpa biaya pendaftaran</li>
          <li>✅ Proses cepat dan mudah</li>
          <li>✅ Keamanan data terjamin</li>
          <li>✅ Dukungan 24/7</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  nama: '',
  email: '',
  nomor_hp: '',
  password: '',
  confirmPassword: '',
  role: 'penumpang',
  no_sim: ''
})

onMounted(() => {
  authStore.clearError()
})

async function handleRegister() {
  // Validate password match
  if (form.value.password !== form.value.confirmPassword) {
    authStore.error = 'Password tidak sama'
    return
  }
  
  const userData = {
    nama: form.value.nama,
    email: form.value.email,
    nomor_hp: form.value.nomor_hp,
    password: form.value.password,
    role: form.value.role
  }
  
  // Add SIM if driver
  if (form.value.role === 'driver' || form.value.role === 'both') {
    userData.no_sim = form.value.no_sim
  }
  
  const success = await authStore.register(userData)
  
  if (success) {
    router.push('/')
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-color) 0%, #7C3AED 100%);
  padding: 20px;
}

.auth-container {
  display: flex;
  max-width: 900px;
  width: 100%;
  background: var(--white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

.auth-card {
  flex: 1;
  padding: 40px;
  max-height: 90vh;
  overflow-y: auto;
}

.auth-header {
  text-align: center;
  margin-bottom: 30px;
}

.auth-logo {
  font-size: 32px;
  font-weight: 700;
  color: var(--primary-color);
  display: block;
  margin-bottom: 20px;
}

.auth-header h1 {
  font-size: 24px;
  margin-bottom: 8px;
}

.auth-header p {
  color: var(--gray-color);
}

.auth-form {
  margin-bottom: 20px;
}

.auth-footer {
  text-align: center;
  color: var(--gray-color);
}

.auth-footer a {
  font-weight: 500;
}

/* Role Cards */
.role-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.role-card {
  border: 2px solid var(--border-color);
  border-radius: var(--radius);
  padding: 16px 12px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--white);
}

.role-card:hover {
  border-color: var(--primary-color);
  background: #F5F3FF;
}

.role-card.active {
  border-color: var(--primary-color);
  background: #F5F3FF;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.role-icon {
  font-size: 32px;
  margin-bottom: 8px;
}

.role-name {
  font-weight: 600;
  font-size: 14px;
  color: var(--dark-color);
  margin-bottom: 4px;
}

.role-desc {
  font-size: 11px;
  color: var(--gray-color);
  line-height: 1.4;
}

.auth-info {
  flex: 1;
  background: var(--primary-color);
  color: var(--white);
  padding: 50px 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.auth-info h2 {
  font-size: 28px;
  margin-bottom: 20px;
}

.auth-info p {
  opacity: 0.9;
  margin-bottom: 30px;
  line-height: 1.7;
}

.auth-features {
  list-style: none;
}

.auth-features li {
  margin-bottom: 12px;
  font-size: 16px;
}

@media (max-width: 768px) {
  .auth-container {
    flex-direction: column;
  }
  
  .auth-info {
    display: none;
  }
  
  .role-cards {
    grid-template-columns: 1fr;
  }
  
  .role-card {
    display: flex;
    align-items: center;
    gap: 12px;
    text-align: left;
    padding: 12px 16px;
  }
  
  .role-icon {
    font-size: 28px;
    margin-bottom: 0;
  }
  
  .role-card > div:last-child {
    flex: 1;
  }
}
</style>
