import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import './assets/style.css'

const app = createApp(App)
const pinia = createPinia()

// Pinia harus di-use SEBELUM router
app.use(pinia)

// Import router SETELAH pinia di-setup
import router from './router'
app.use(router)

app.mount('#app')
