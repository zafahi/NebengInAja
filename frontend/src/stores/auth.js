import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(JSON.parse(localStorage.getItem('user')) || null)
  const token = ref(localStorage.getItem('token') || null)
  const loading = ref(false)
  const error = ref(null)

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const isDriver = computed(() => {
    return user.value?.role === 'driver' || user.value?.role === 'both'
  })
  const userName = computed(() => user.value?.nama || '')

  // Actions
  async function login(email, password) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post('/auth/login', { email, password })
      
      console.log('Login response:', response.data) // Debug
      
      if (response.data.success) {
        user.value = response.data.data.user
        token.value = response.data.data.token
        
        localStorage.setItem('user', JSON.stringify(user.value))
        localStorage.setItem('token', token.value)
        
        // Verify storage
        console.log('Stored token:', localStorage.getItem('token'))
        console.log('Stored user:', localStorage.getItem('user'))
        
        return true
      } else {
        error.value = response.data.message
        return false
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Terjadi kesalahan saat login'
      return false
    } finally {
      loading.value = false
    }
  }

  async function register(userData) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post('/auth/register', userData)
      
      if (response.data.success) {
        user.value = response.data.data.user
        token.value = response.data.data.token
        
        localStorage.setItem('user', JSON.stringify(user.value))
        localStorage.setItem('token', token.value)
        
        return true
      } else {
        error.value = response.data.message
        return false
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Terjadi kesalahan saat registrasi'
      return false
    } finally {
      loading.value = false
    }
  }

  async function fetchProfile() {
    if (!token.value) return
    
    try {
      const response = await api.get('/users/me')
      
      if (response.data.success) {
        user.value = response.data.data
        localStorage.setItem('user', JSON.stringify(user.value))
      }
    } catch (err) {
      console.error('Failed to fetch profile:', err)
    }
  }

  function logout() {
    user.value = null
    token.value = null
    localStorage.removeItem('user')
    localStorage.removeItem('token')
  }

  function clearError() {
    error.value = null
  }

  return {
    // State
    user,
    token,
    loading,
    error,
    // Getters
    isAuthenticated,
    isDriver,
    userName,
    // Actions
    login,
    register,
    fetchProfile,
    logout,
    clearError
  }
})
