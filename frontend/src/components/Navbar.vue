<template>
  <nav class="navbar">
    <div class="container navbar-container">
      <router-link to="/" class="navbar-brand">
        🚗 Nebeng
      </router-link>
      
      <div class="navbar-menu" :class="{ active: menuOpen }">
        <router-link to="/trips" class="nav-link">Cari Tumpangan</router-link>
        
        <template v-if="authStore.isAuthenticated">
          <router-link v-if="authStore.isDriver" to="/trips/create" class="nav-link">
            Tawarkan Tumpangan
          </router-link>
          <router-link to="/my-bookings" class="nav-link">Pemesanan Saya</router-link>
          <router-link v-if="authStore.isDriver" to="/my-trips" class="nav-link">
            Trip Saya
          </router-link>
          
          <div class="nav-dropdown">
            <button class="nav-dropdown-toggle">
              {{ authStore.userName }}
              <span class="dropdown-arrow">▼</span>
            </button>
            <div class="nav-dropdown-menu">
              <div class="nav-dropdown-menu-inner">
                <router-link to="/profile" class="dropdown-item">Profil</router-link>
                <router-link v-if="authStore.isDriver" to="/my-vehicles" class="dropdown-item">
                  Kendaraan Saya
                </router-link>
                <router-link v-else to="/become-driver" class="dropdown-item">
                  Jadi Driver
                </router-link>
                <hr class="dropdown-divider">
                <button @click="handleLogout" class="dropdown-item text-danger">
                  Logout
                </button>
              </div>
            </div>
          </div>
        </template>
        
        <template v-else>
          <router-link to="/login" class="nav-link">Login</router-link>
          <router-link to="/register" class="btn btn-primary btn-sm">
            Daftar
          </router-link>
        </template>
      </div>
      
      <button class="navbar-toggle" @click="menuOpen = !menuOpen">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const menuOpen = ref(false)

function handleLogout() {
  authStore.logout()
  router.push('/')
}
</script>

<style scoped>
.navbar {
  background-color: var(--white);
  box-shadow: var(--shadow);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  height: 70px;
}

.navbar-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 100%;
}

.navbar-brand {
  font-size: 24px;
  font-weight: 700;
  color: var(--primary-color);
}

.navbar-menu {
  display: flex;
  align-items: center;
  gap: 24px;
}

.nav-link {
  color: var(--dark-color);
  font-weight: 500;
  transition: color 0.2s;
}

.nav-link:hover {
  color: var(--primary-color);
}

.nav-link.router-link-active {
  color: var(--primary-color);
}

/* Dropdown */
.nav-dropdown {
  position: relative;
}

.nav-dropdown-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--light-gray);
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  font-weight: 500;
}

.dropdown-arrow {
  font-size: 10px;
}

.nav-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  padding-top: 8px;
  background: transparent;
  min-width: 180px;
  display: none;
}

.nav-dropdown-menu::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 8px;
}

.nav-dropdown-menu > * {
  background: var(--white);
}

.nav-dropdown-menu .dropdown-item:first-child {
  border-radius: var(--radius) var(--radius) 0 0;
}

.nav-dropdown-menu .dropdown-item:last-child {
  border-radius: 0 0 var(--radius) var(--radius);
}

.nav-dropdown-menu-inner {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  padding: 8px 0;
  overflow: hidden;
}

.nav-dropdown:hover .nav-dropdown-menu {
  display: block;
}

.dropdown-item {
  display: block;
  padding: 10px 16px;
  color: var(--dark-color);
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  font-size: 14px;
}

.dropdown-item:hover {
  background: var(--light-gray);
}

.dropdown-divider {
  border: none;
  border-top: 1px solid var(--border-color);
  margin: 8px 0;
}

/* Mobile Toggle */
.navbar-toggle {
  display: none;
  flex-direction: column;
  gap: 5px;
  padding: 8px;
  background: none;
  border: none;
  cursor: pointer;
}

.navbar-toggle span {
  display: block;
  width: 24px;
  height: 2px;
  background: var(--dark-color);
  transition: transform 0.2s;
}

@media (max-width: 768px) {
  .navbar-toggle {
    display: flex;
  }
  
  .navbar-menu {
    position: fixed;
    top: 70px;
    left: 0;
    right: 0;
    background: var(--white);
    flex-direction: column;
    padding: 20px;
    gap: 16px;
    box-shadow: var(--shadow-lg);
    display: none;
  }
  
  .navbar-menu.active {
    display: flex;
  }
  
  .nav-dropdown-menu {
    position: static;
    padding-top: 0;
  }
  
  .nav-dropdown-menu-inner {
    box-shadow: none;
    border: 1px solid var(--border-color);
    margin-top: 8px;
  }
}
</style>
