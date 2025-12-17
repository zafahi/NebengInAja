-- =============================================
-- DATABASE: NEBENG - Ride Sharing Platform
-- Versi cocok untuk Railway (tanpa CREATE DATABASE/USE)
-- =============================================

-- =============================================
-- TABEL: cities (Daftar Kota)
-- =============================================
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL: users (Semua User - Penumpang & Driver)
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    alamat VARCHAR(255),
    nomor_hp VARCHAR(20) NOT NULL,
    foto_profil VARCHAR(255) DEFAULT 'default.png',
    role ENUM('penumpang', 'driver', 'both') DEFAULT 'penumpang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL: drivers (Info Tambahan untuk Driver)
-- =============================================
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    no_sim VARCHAR(50) NOT NULL,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_trips INT DEFAULT 0,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- TABEL: brands (Merk Kendaraan)
-- =============================================
CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- =============================================
-- TABEL: models (Model Kendaraan)
-- =============================================
CREATE TABLE models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    nama VARCHAR(255) NOT NULL,
    kapasitas INT NOT NULL DEFAULT 4,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- TABEL: vehicles (Kendaraan milik Driver)
-- =============================================
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    brand_id INT NOT NULL,
    model_id INT NOT NULL,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    warna VARCHAR(50),
    tahun INT,
    foto VARCHAR(255) DEFAULT 'default_car.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (brand_id) REFERENCES brands(id),
    FOREIGN KEY (model_id) REFERENCES models(id)
) ENGINE=InnoDB;

-- =============================================
-- TABEL: trips (Perjalanan yang ditawarkan Driver)
-- =============================================
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    origin_city_id INT NOT NULL,
    destination_city_id INT NOT NULL,
    titik_jemput VARCHAR(255) NOT NULL,
    titik_tujuan VARCHAR(255) NOT NULL,
    waktu_keberangkatan DATETIME NOT NULL,
    seat_price INT NOT NULL,
    max_passenger INT NOT NULL,
    available_seats INT NOT NULL,
    deskripsi TEXT,
    status ENUM('menunggu', 'aktif', 'dalam_perjalanan', 'selesai', 'dibatalkan') DEFAULT 'menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (origin_city_id) REFERENCES cities(id),
    FOREIGN KEY (destination_city_id) REFERENCES cities(id)
) ENGINE=InnoDB;

-- =============================================
-- TABEL: bookings (Pemesanan Tumpangan)
-- =============================================
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    passenger_id INT NOT NULL,
    seat_count INT NOT NULL DEFAULT 1,
    pickup_location VARCHAR(255) NOT NULL,
    dropoff_location VARCHAR(255),
    jarak_km DECIMAL(10,2),
    fare INT NOT NULL,
    
    -- Status Booking
    booking_status ENUM('pending', 'diterima', 'ditolak', 'dibatalkan', 'dalam_perjalanan', 'selesai') DEFAULT 'pending',
    
    -- Status Pembayaran
    payment_status ENUM('belum_bayar', 'menunggu_konfirmasi', 'berhasil', 'gagal', 'refund') DEFAULT 'belum_bayar',
    payment_method VARCHAR(50),
    paid_at DATETIME,
    
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- TABEL: reviews (Review & Rating)
-- =============================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id),
    FOREIGN KEY (reviewee_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- =============================================
-- DATA AWAL: Kota-kota di Indonesia
-- =============================================
INSERT INTO cities (nama) VALUES 
('Jakarta'),
('Bandung'),
('Surabaya'),
('Yogyakarta'),
('Semarang'),
('Malang'),
('Solo'),
('Cirebon'),
('Bogor'),
('Depok'),
('Tangerang'),
('Bekasi');

-- =============================================
-- DATA AWAL: Brand Kendaraan
-- =============================================
INSERT INTO brands (nama) VALUES 
('Toyota'),
('Honda'),
('Suzuki'),
('Daihatsu'),
('Mitsubishi'),
('Nissan'),
('Mazda'),
('Hyundai'),
('Kia'),
('Wuling');

-- =============================================
-- DATA AWAL: Model Kendaraan
-- =============================================
INSERT INTO models (brand_id, nama, kapasitas) VALUES 
-- Toyota
(1, 'Avanza', 7),
(1, 'Innova', 7),
(1, 'Calya', 7),
(1, 'Rush', 7),
(1, 'Yaris', 4),
-- Honda
(2, 'Brio', 4),
(2, 'Jazz', 4),
(2, 'Mobilio', 7),
(2, 'HR-V', 5),
(2, 'CR-V', 5),
-- Suzuki
(3, 'Ertiga', 7),
(3, 'XL7', 7),
(3, 'Swift', 4),
-- Daihatsu
(4, 'Xenia', 7),
(4, 'Terios', 7),
(4, 'Ayla', 4),
-- Mitsubishi
(5, 'Xpander', 7),
(5, 'Pajero Sport', 7),
-- Wuling
(10, 'Confero', 7),
(10, 'Almaz', 7);

-- =============================================
-- DATA DUMMY: User untuk Testing
-- Password: 123456 (hashed)
-- =============================================
INSERT INTO users (nama, email, password, alamat, nomor_hp, role) VALUES 
('Budi Santoso', 'budi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jl. Merdeka No. 10, Jakarta', '081234567890', 'both'),
('Siti Rahayu', 'siti@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jl. Sudirman No. 25, Jakarta', '081234567891', 'penumpang'),
('Agus Wijaya', 'agus@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jl. Gatot Subroto No. 15, Bandung', '081234567892', 'driver'),
('Dewi Lestari', 'dewi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jl. Asia Afrika No. 50, Bandung', '081234567893', 'penumpang'),
('Andi Pratama', 'andi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jl. Thamrin No. 100, Jakarta', '081234567894', 'both');

-- Driver data
INSERT INTO drivers (user_id, no_sim, rating, total_trips, is_verified) VALUES 
(1, 'SIM123456789', 4.80, 25, 1),
(3, 'SIM987654321', 4.50, 10, 1),
(5, 'SIM456789123', 4.90, 50, 1);

-- Vehicle data
INSERT INTO vehicles (user_id, brand_id, model_id, plate_number, warna, tahun) VALUES 
(1, 1, 1, 'B 1234 ABC', 'Putih', 2020),
(3, 2, 8, 'D 5678 XYZ', 'Hitam', 2021),
(5, 5, 17, 'B 9999 DEF', 'Silver', 2022);

-- Sample Trips
INSERT INTO trips (driver_id, vehicle_id, origin_city_id, destination_city_id, titik_jemput, titik_tujuan, waktu_keberangkatan, seat_price, max_passenger, available_seats, deskripsi, status) VALUES 
(1, 1, 1, 2, 'Terminal Pulo Gebang, Jakarta Timur', 'Terminal Leuwi Panjang, Bandung', '2025-12-10 08:00:00', 75000, 6, 4, 'Berangkat pagi, AC dingin, bagasi luas. Bisa request berhenti di rest area.', 'aktif'),
(2, 2, 2, 1, 'Pasteur, Bandung', 'Gambir, Jakarta', '2025-12-11 07:00:00', 80000, 4, 3, 'Mobil bersih dan nyaman. Non-smoking.', 'aktif'),
(3, 3, 1, 4, 'Cawang, Jakarta', 'Malioboro, Yogyakarta', '2025-12-12 06:00:00', 150000, 6, 5, 'Perjalanan Jakarta-Jogja. Istirahat 2x di rest area.', 'menunggu');

-- Sample Bookings
INSERT INTO bookings (trip_id, passenger_id, seat_count, pickup_location, dropoff_location, jarak_km, fare, booking_status, payment_status, catatan) VALUES 
(1, 2, 2, 'Cibubur Junction', 'Terminal Leuwi Panjang', 145.5, 150000, 'diterima', 'berhasil', 'Tolong jemput di depan mall ya'),
(1, 4, 1, 'Bekasi Barat', 'Pasteur, Bandung', 160.0, 75000, 'pending', 'belum_bayar', NULL);

-- =============================================
-- INDEX untuk optimasi query
-- =============================================
CREATE INDEX idx_trips_status ON trips(status);
CREATE INDEX idx_trips_waktu ON trips(waktu_keberangkatan);
CREATE INDEX idx_trips_origin ON trips(origin_city_id);
CREATE INDEX idx_trips_destination ON trips(destination_city_id);
CREATE INDEX idx_bookings_status ON bookings(booking_status);
CREATE INDEX idx_bookings_payment ON bookings(payment_status);

