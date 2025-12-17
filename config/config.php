<?php
/**
 * Konfigurasi Aplikasi
 * Nebeng - Ride Sharing Platform
 */

// Timezone
date_default_timezone_set('Asia/Jakarta');

// JWT Secret Key (untuk authentication)
define('JWT_SECRET', 'nebeng_secret_key_2025_change_this_in_production');

// Upload Path
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Base URL
define('BASE_URL', 'http://localhost/nebenginaja/');

// API URL
define('API_URL', BASE_URL . 'api/');

// Session lifetime (dalam detik)
define('SESSION_LIFETIME', 86400); // 24 jam

// Harga per KM (dalam Rupiah)
define('PRICE_PER_KM', 500);

// Status Trip
define('TRIP_STATUS', [
    'menunggu' => 'Menunggu',
    'aktif' => 'Aktif',
    'dalam_perjalanan' => 'Dalam Perjalanan',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan'
]);

// Status Booking
define('BOOKING_STATUS', [
    'pending' => 'Menunggu Konfirmasi',
    'diterima' => 'Diterima',
    'ditolak' => 'Ditolak',
    'dibatalkan' => 'Dibatalkan',
    'dalam_perjalanan' => 'Dalam Perjalanan',
    'selesai' => 'Selesai'
]);

// Status Pembayaran
define('PAYMENT_STATUS', [
    'belum_bayar' => 'Belum Bayar',
    'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
    'berhasil' => 'Berhasil',
    'gagal' => 'Gagal',
    'refund' => 'Refund'
]);

// Role User
define('USER_ROLES', [
    'penumpang' => 'Penumpang',
    'driver' => 'Driver',
    'both' => 'Driver & Penumpang'
]);
