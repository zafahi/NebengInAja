import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json'
  }
})

// Request interceptor - add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor - handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Hanya hapus token jika 401 dan BUKAN dari halaman login/register
    if (error.response?.status === 401) {
      const currentPath = window.location.pathname
      // Jangan auto-logout jika sedang di halaman auth
      if (!currentPath.includes('/login') && !currentPath.includes('/register')) {
        // Cek apakah ini error karena token expired, bukan karena credentials salah
        const isTokenError = error.response?.data?.message?.toLowerCase().includes('token')
        if (isTokenError) {
          localStorage.removeItem('token')
          localStorage.removeItem('user')
          window.location.href = '/login'
        }
      }
    }
    return Promise.reject(error)
  }
)

export default api
