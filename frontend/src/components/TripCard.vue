<template>
  <router-link :to="`/trips/${trip.id}`" class="trip-card">
    <div class="trip-route">
      <span class="trip-city">{{ trip.origin_city }}</span>
      <span class="trip-arrow">→</span>
      <span class="trip-city">{{ trip.destination_city }}</span>
    </div>
    
    <div class="trip-info">
      <div class="trip-info-item">
        📅 {{ formatDate(trip.waktu_keberangkatan) }}
      </div>
      <div class="trip-info-item">
        🕐 {{ formatTime(trip.waktu_keberangkatan) }}
      </div>
      <div class="trip-info-item">
        💺 {{ trip.available_seats }} kursi tersedia
      </div>
    </div>
    
    <div class="d-flex justify-between align-center">
      <span class="trip-price">{{ formatRupiah(trip.seat_price) }}/kursi</span>
      <span class="badge" :class="statusClass">{{ statusLabel }}</span>
    </div>
    
    <div class="trip-driver">
      <img :src="driverAvatar" :alt="trip.driver_nama" class="trip-driver-avatar">
      <div class="trip-driver-info">
        <div class="trip-driver-name">{{ trip.driver_nama }}</div>
        <div class="trip-driver-rating">
          ⭐ {{ trip.driver_rating || '0.00' }} • {{ trip.brand_nama }} {{ trip.model_nama }}
        </div>
      </div>
    </div>
  </router-link>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  trip: {
    type: Object,
    required: true
  }
})

const driverAvatar = computed(() => {
  return props.trip.driver_foto && props.trip.driver_foto !== 'default.png'
    ? `/uploads/${props.trip.driver_foto}`
    : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(props.trip.driver_nama) + '&background=4F46E5&color=fff'
})

const statusClass = computed(() => {
  const statusMap = {
    'menunggu': 'badge-warning',
    'aktif': 'badge-success',
    'dalam_perjalanan': 'badge-info',
    'selesai': 'badge-primary',
    'dibatalkan': 'badge-danger'
  }
  return statusMap[props.trip.status] || 'badge-primary'
})

const statusLabel = computed(() => {
  const statusMap = {
    'menunggu': 'Menunggu',
    'aktif': 'Aktif',
    'dalam_perjalanan': 'Dalam Perjalanan',
    'selesai': 'Selesai',
    'dibatalkan': 'Dibatalkan'
  }
  return statusMap[props.trip.status] || props.trip.status
})

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

function formatTime(dateString) {
  const date = new Date(dateString)
  return date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatRupiah(amount) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}
</script>

<style scoped>
.trip-card {
  display: block;
  text-decoration: none;
  color: inherit;
}
</style>
