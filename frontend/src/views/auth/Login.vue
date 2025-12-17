<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <div class="auth-header">
          <router-link to="/" class="auth-logo">🚗 Nebeng</router-link>
          <h1>Selamat Datang Kembali</h1>
          <p>Masuk ke akun Anda untuk melanjutkan</p>
        </div>
        
        <form @submit.prevent="handleLogin" class="auth-form">
          <div v-if="authStore.error" class="alert alert-danger">
            {{ authStore.error }}
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
            <label class="form-label">Password</label>
            <input 
              type="password" 
              v-model="form.password" 
              class="form-control" 
              placeholder="Masukkan password"
              required
            >
          </div>
          
          <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="authStore.loading">
            {{ authStore.loading ? 'Memproses...' : 'Login' }}
          </button>
        </form>
        
        <div class="auth-footer">
          <p>Belum punya akun? <router-link to="/register">Daftar sekarang</router-link></p>
        </div>
      </div>
      
      <div class="auth-info">
        <h2>Mulai Perjalananmu</h2>
        <p>Bergabung dengan ribuan pengguna Nebeng dan nikmati perjalanan yang lebih hemat dan menyenangkan.</p>
        <ul class="auth-features">
          <li>✅ Hemat biaya perjalanan</li>
          <li>✅ Bertemu teman baru</li>
          <li>✅ Ramah lingkungan</li>
          <li>✅ Aman dan terpercaya</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: ''
})

onMounted(() => {
  authStore.clearError()
})

async function handleLogin() {
  const success = await authStore.login(form.value.email, form.value.password)
  
  if (success) {
    const redirect = route.query.redirect || '/'
    router.push(redirect)
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
  padding: 50px 40px;
}

.auth-header {
  text-align: center;
  margin-bottom: 40px;
}

.auth-logo {
  font-size: 32px;
  font-weight: 700;
  color: var(--primary-color);
  display: block;
  margin-bottom: 30px;
}

.auth-header h1 {
  font-size: 28px;
  margin-bottom: 10px;
}

.auth-header p {
  color: var(--gray-color);
}

.auth-form {
  margin-bottom: 30px;
}

.auth-footer {
  text-align: center;
  color: var(--gray-color);
}

.auth-footer a {
  font-weight: 500;
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
}
</style>
