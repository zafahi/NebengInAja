<template>
  <div class="driver-profile-page">
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Memuat profil driver...</p>
    </div>
    
    <div v-else-if="!driver" class="container">
      <div class="empty-state">
        <div class="empty-state-icon">❌</div>
        <h3 class="empty-state-title">Driver Tidak Ditemukan</h3>
        <router-link to="/trips" class="btn btn-primary">Kembali ke Daftar Trip</router-link>
      </div>
    </div>
    
    <template v-else>
      <div class="page-header">
        <div class="container">
          <button @click="$router.back()" class="back-link">← Kembali</button>
          <h1 class="page-title">Profil Driver</h1>
        </div>
      </div>
      
      <div class="container">
        <div class="profile-grid">
          <!-- Driver Info Card -->
          <div class="profile-main">
            <div class="card driver-card">
              <div class="card-body">
                <div class="driver-header">
                  <img :src="driverAvatar" :alt="driver.nama" class="driver-avatar-lg">
                  <div class="driver-info">
                    <h2>{{ driver.nama }}</h2>
                    <div class="driver-stats">
                      <div class="stat">
                        <span class="stat-icon">⭐</span>
                        <span class="stat-value">{{ driver.rating || '0.00' }}</span>
                        <span class="stat-label">Rating</span>
                      </div>
                      <div class="stat">
                        <span class="stat-icon">🚗</span>
                        <span class="stat-value">{{ driver.total_trips || 0 }}</span>
                        <span class="stat-label">Trip</span>
                      </div>
                      <div class="stat">
                        <span class="stat-icon">📝</span>
                        <span class="stat-value">{{ reviewStats.total_reviews }}</span>
                        <span class="stat-label">Review</span>
                      </div>
                    </div>
                    <div v-if="driver.is_verified" class="verified-badge">
                      ✓ Driver Terverifikasi
                    </div>
                  </div>
                </div>
                
                <div class="driver-meta">
                  <div class="meta-item">
                    <span class="meta-label">Bergabung sejak</span>
                    <span class="meta-value">{{ formatJoinDate(driver.created_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="card">
              <div class="card-header">
                <h2>Review dari Penumpang</h2>
                <span class="review-avg" v-if="reviewStats.total_reviews > 0">
                  ⭐ {{ reviewStats.average_rating }} ({{ reviewStats.total_reviews }} review)
                </span>
              </div>
              <div class="card-body">
                <div v-if="reviews.length === 0" class="empty-reviews">
                  <p>Belum ada review untuk driver ini.</p>
                </div>
                
                <div v-else class="reviews-list">
                  <div v-for="review in reviews" :key="review.id" class="review-item">
                    <div class="review-header">
                      <img :src="getReviewerAvatar(review)" :alt="review.reviewer_nama" class="reviewer-avatar">
                      <div class="reviewer-info">
                        <span class="reviewer-name">{{ review.reviewer_nama }}</span>
                        <span class="review-date">{{ formatDate(review.created_at) }}</span>
                      </div>
                      <div class="review-rating">
                        <span v-for="i in 5" :key="i" class="star" :class="{ filled: i <= review.rating }">★</span>
                      </div>
                    </div>
                    <p v-if="review.komentar" class="review-comment">{{ review.komentar }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Sidebar -->
          <div class="profile-sidebar">
            <div class="card">
              <div class="card-header">
                <h3>Rating Breakdown</h3>
              </div>
              <div class="card-body">
                <div class="rating-bars">
                  <div v-for="i in 5" :key="i" class="rating-bar">
                    <span class="rating-label">{{ 6-i }} ★</span>
                    <div class="bar-bg">
                      <div class="bar-fill" :style="{ width: getRatingPercentage(6-i) + '%' }"></div>
                    </div>
                    <span class="rating-count">{{ getRatingCount(6-i) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../services/api'

const route = useRoute()

const loading = ref(true)
const driver = ref(null)
const reviews = ref([])
const reviewStats = ref({ average_rating: 0, total_reviews: 0 })

const driverAvatar = computed(() => {
  if (!driver.value) return ''
  return driver.value.foto_profil && driver.value.foto_profil !== 'default.png'
    ? `/uploads/${driver.value.foto_profil}`
    : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(driver.value.nama) + '&background=4F46E5&color=fff&size=128'
})

onMounted(async () => {
  await Promise.all([fetchDriver(), fetchReviews()])
  loading.value = false
})

async function fetchDriver() {
  try {
    const response = await api.get(`/users/${route.params.id}`)
    if (response.data.success) {
      driver.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch driver:', error)
  }
}

async function fetchReviews() {
  try {
    const response = await api.get(`/reviews/user/${route.params.id}`)
    if (response.data.success) {
      reviews.value = response.data.data.reviews
      reviewStats.value = {
        average_rating: response.data.data.average_rating,
        total_reviews: response.data.data.total_reviews
      }
    }
  } catch (error) {
    console.error('Failed to fetch reviews:', error)
  }
}

function getReviewerAvatar(review) {
  return review.reviewer_foto && review.reviewer_foto !== 'default.png'
    ? `/uploads/${review.reviewer_foto}`
    : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(review.reviewer_nama) + '&background=10B981&color=fff&size=64'
}

function getRatingCount(rating) {
  return reviews.value.filter(r => r.rating === rating).length
}

function getRatingPercentage(rating) {
  if (reviews.value.length === 0) return 0
  return (getRatingCount(rating) / reviews.value.length) * 100
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

function formatJoinDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    month: 'long',
    year: 'numeric'
  })
}
</script>

<style scoped>
.page-header {
  background: linear-gradient(135deg, var(--primary-color) 0%, #7C3AED 100%);
  color: var(--white);
  padding: 40px 0;
}

.back-link {
  color: rgba(255, 255, 255, 0.8);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  margin-bottom: 10px;
  display: inline-block;
}

.back-link:hover {
  color: var(--white);
}

.page-title {
  font-size: 28px;
  margin: 0;
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 30px 20px;
}

.profile-grid {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 24px;
}

.profile-main {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Driver Card */
.driver-card .card-body {
  padding: 30px;
}

.driver-header {
  display: flex;
  gap: 24px;
  align-items: flex-start;
}

.driver-avatar-lg {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
}

.driver-info h2 {
  font-size: 28px;
  margin-bottom: 16px;
}

.driver-stats {
  display: flex;
  gap: 24px;
  margin-bottom: 16px;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 20px;
  background: var(--light-gray);
  border-radius: var(--radius);
}

.stat-icon {
  font-size: 20px;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
}

.stat-label {
  font-size: 12px;
  color: var(--gray-color);
}

.verified-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: #D1FAE5;
  color: #065F46;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
}

.driver-meta {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--border-color);
}

.meta-item {
  display: flex;
  gap: 8px;
}

.meta-label {
  color: var(--gray-color);
}

/* Reviews */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.review-avg {
  font-size: 14px;
  color: var(--gray-color);
}

.empty-reviews {
  text-align: center;
  padding: 40px;
  color: var(--gray-color);
}

.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.review-item {
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border-color);
}

.review-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.review-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.reviewer-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.reviewer-info {
  flex: 1;
}

.reviewer-name {
  display: block;
  font-weight: 500;
}

.review-date {
  font-size: 12px;
  color: var(--gray-color);
}

.review-rating .star {
  color: #E5E7EB;
  font-size: 16px;
}

.review-rating .star.filled {
  color: #FBBF24;
}

.review-comment {
  color: var(--dark-color);
  line-height: 1.6;
  margin: 0;
}

/* Rating Bars */
.rating-bars {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.rating-bar {
  display: flex;
  align-items: center;
  gap: 8px;
}

.rating-label {
  width: 35px;
  font-size: 13px;
  color: var(--gray-color);
}

.bar-bg {
  flex: 1;
  height: 8px;
  background: var(--light-gray);
  border-radius: 4px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  background: #FBBF24;
  border-radius: 4px;
  transition: width 0.3s;
}

.rating-count {
  width: 20px;
  font-size: 12px;
  color: var(--gray-color);
  text-align: right;
}

/* Responsive */
@media (max-width: 768px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
  
  .driver-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  
  .driver-stats {
    justify-content: center;
  }
}
</style>
