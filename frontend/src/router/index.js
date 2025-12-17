import { createRouter, createWebHistory } from 'vue-router'

// Views
import Home from '../views/Home.vue'
import Login from '../views/auth/Login.vue'
import Register from '../views/auth/Register.vue'
import Trips from '../views/trips/Trips.vue'
import TripDetail from '../views/trips/TripDetail.vue'
import CreateTrip from '../views/trips/CreateTrip.vue'
import MyTrips from '../views/trips/MyTrips.vue'
import MyBookings from '../views/bookings/MyBookings.vue'
import BookingDetail from '../views/bookings/BookingDetail.vue'
import Profile from '../views/user/Profile.vue'
import MyVehicles from '../views/user/MyVehicles.vue'
import BecomeDriver from '../views/user/BecomeDriver.vue'
import DriverProfile from '../views/user/DriverProfile.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { guest: true }
  },
  {
    path: '/trips',
    name: 'Trips',
    component: Trips
  },
  {
    path: '/trips/:id',
    name: 'TripDetail',
    component: TripDetail
  },
  {
    path: '/trips/create',
    name: 'CreateTrip',
    component: CreateTrip,
    meta: { requiresAuth: true, requiresDriver: true }
  },
  {
    path: '/my-trips',
    name: 'MyTrips',
    component: MyTrips,
    meta: { requiresAuth: true, requiresDriver: true }
  },
  {
    path: '/my-bookings',
    name: 'MyBookings',
    component: MyBookings,
    meta: { requiresAuth: true }
  },
  {
    path: '/bookings/:id',
    name: 'BookingDetail',
    component: BookingDetail,
    meta: { requiresAuth: true }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
    meta: { requiresAuth: true }
  },
  {
    path: '/my-vehicles',
    name: 'MyVehicles',
    component: MyVehicles,
    meta: { requiresAuth: true, requiresDriver: true }
  },
  {
    path: '/become-driver',
    name: 'BecomeDriver',
    component: BecomeDriver,
    meta: { requiresAuth: true }
  },
  {
    path: '/driver/:id',
    name: 'DriverProfile',
    component: DriverProfile
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation Guard
router.beforeEach((to, from, next) => {
  // Cek token langsung dari localStorage untuk menghindari masalah timing dengan Pinia
  const token = localStorage.getItem('token')
  const userStr = localStorage.getItem('user')
  
  // Debug logging - bisa dihapus nanti
  console.log('Navigation Guard Debug:', {
    to: to.path,
    token: token ? 'exists' : 'null',
    userStr: userStr ? 'exists' : 'null',
    requiresAuth: to.meta.requiresAuth
  })
  
  const user = userStr ? JSON.parse(userStr) : null
  
  const isAuthenticated = !!token && token !== 'null' && token !== 'undefined'
  const isDriver = user?.role === 'driver' || user?.role === 'both'
  
  // Check if route requires auth
  if (to.meta.requiresAuth && !isAuthenticated) {
    console.log('Redirecting to login - not authenticated')
    next({ name: 'Login', query: { redirect: to.fullPath } })
    return
  }
  
  // Check if route is for guests only
  if (to.meta.guest && isAuthenticated) {
    next({ name: 'Home' })
    return
  }
  
  // Check if route requires driver role
  if (to.meta.requiresDriver && !isDriver) {
    next({ name: 'BecomeDriver' })
    return
  }
  
  next()
})

export default router
