<template>
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <h3>🚗 Nebeng</h3>
          <p>Platform berbagi tumpangan untuk perjalanan yang lebih hemat dan ramah lingkungan.</p>
        </div>
        
        <div class="footer-links">
          <h4>Menu</h4>
          <ul>
            <li><router-link to="/">Beranda</router-link></li>
            <li><router-link to="/trips">Cari Tumpangan</router-link></li>
            <li><router-link to="/become-driver">Jadi Driver</router-link></li>
          </ul>
        </div>
        
        <div class="footer-links">
          <h4>Bantuan</h4>
          <ul>
            <li><a href="#">Cara Kerja</a></li>
            <li><a href="#">FAQ</a></li>
            <li><a href="#">Kontak Kami</a></li>
          </ul>
        </div>
        
        <div class="footer-links">
          <h4>Legal</h4>
          <ul>
            <li><a href="#">Syarat & Ketentuan</a></li>
            <li><a href="#">Kebijakan Privasi</a></li>
          </ul>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; {{ currentYear }} Nebeng. All rights reserved.</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear()
</script>

<style scoped>
.footer {
  background-color: var(--dark-color);
  color: var(--white);
  padding: 60px 0 30px;
  margin-top: 60px;
}

.footer-content {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 40px;
  margin-bottom: 40px;
}

.footer-brand h3 {
  font-size: 24px;
  margin-bottom: 16px;
}

.footer-brand p {
  color: #9CA3AF;
  line-height: 1.7;
}

.footer-links h4 {
  font-size: 16px;
  margin-bottom: 20px;
}

.footer-links ul {
  list-style: none;
}

.footer-links li {
  margin-bottom: 12px;
}

.footer-links a {
  color: #9CA3AF;
  transition: color 0.2s;
}

.footer-links a:hover {
  color: var(--white);
}

.footer-bottom {
  padding-top: 30px;
  border-top: 1px solid #374151;
  text-align: center;
  color: #9CA3AF;
  font-size: 14px;
}

@media (max-width: 768px) {
  .footer-content {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 576px) {
  .footer-content {
    grid-template-columns: 1fr;
    text-align: center;
  }
}
</style>
