<?php
/**
 * Bookings API
 * Nebeng - Ride Sharing Platform
 */

$database = new Database();
$db = $database->getConnection();

switch ($requestMethod) {
    case 'GET':
        if ($id === 'my-bookings') {
            getMyBookings($db);
        } elseif ($id === 'trip' && $action) {
            getBookingsByTrip($db, $action);
        } elseif ($id) {
            getBookingById($db, $id);
        } else {
            sendError('Endpoint tidak valid', 404);
        }
        break;
    case 'POST':
        createBooking($db);
        break;
    case 'PUT':
        if ($id && $action === 'status') {
            updateBookingStatus($db, $id);
        } elseif ($id && $action === 'payment') {
            updatePaymentStatus($db, $id);
        } else {
            sendError('Endpoint tidak valid', 404);
        }
        break;
    case 'DELETE':
        if ($id) {
            cancelBooking($db, $id);
        } else {
            sendError('ID booking diperlukan', 400);
        }
        break;
    default:
        sendError('Method tidak diizinkan', 405);
}

/**
 * Get My Bookings (Passenger)
 */
function getMyBookings($db) {
    $auth = requireAuth();
    
    $status = isset($_GET['status']) ? sanitize($_GET['status']) : null;
    
    $conditions = ["b.passenger_id = ?"];
    $params = [$auth['user_id']];
    
    if ($status) {
        $conditions[] = "b.booking_status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    $stmt = $db->prepare("
        SELECT b.*, 
               t.titik_jemput as trip_titik_jemput, t.titik_tujuan as trip_titik_tujuan,
               t.waktu_keberangkatan, t.status as trip_status,
               oc.nama as origin_city, dc.nama as destination_city,
               u.nama as driver_nama, u.foto_profil as driver_foto, u.nomor_hp as driver_hp,
               d.rating as driver_rating,
               v.plate_number, br.nama as brand_nama, m.nama as model_nama
        FROM bookings b
        JOIN trips t ON b.trip_id = t.id
        JOIN cities oc ON t.origin_city_id = oc.id
        JOIN cities dc ON t.destination_city_id = dc.id
        JOIN drivers d ON t.driver_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN brands br ON v.brand_id = br.id
        JOIN models m ON v.model_id = m.id
        WHERE $whereClause
        ORDER BY b.created_at DESC
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($bookings);
}

/**
 * Get Bookings by Trip (For Driver)
 */
function getBookingsByTrip($db, $tripId) {
    $auth = requireAuth();
    
    // Verify driver ownership of trip
    $stmt = $db->prepare("
        SELECT t.id FROM trips t
        JOIN drivers d ON t.driver_id = d.id
        WHERE t.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$tripId, $auth['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        sendError('Trip tidak ditemukan atau bukan milik Anda', 403);
    }
    
    $stmt = $db->prepare("
        SELECT b.*, u.nama as passenger_nama, u.foto_profil as passenger_foto,
               u.nomor_hp as passenger_hp
        FROM bookings b
        JOIN users u ON b.passenger_id = u.id
        WHERE b.trip_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$tripId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($bookings);
}

/**
 * Get Booking by ID
 */
function getBookingById($db, $bookingId) {
    $auth = requireAuth();
    
    $stmt = $db->prepare("
        SELECT b.*, 
               t.titik_jemput as trip_titik_jemput, t.titik_tujuan as trip_titik_tujuan,
               t.waktu_keberangkatan, t.status as trip_status, t.deskripsi as trip_deskripsi,
               oc.nama as origin_city, dc.nama as destination_city,
               u.id as driver_user_id, u.nama as driver_nama, u.foto_profil as driver_foto, 
               u.nomor_hp as driver_hp,
               d.rating as driver_rating, d.is_verified,
               v.plate_number, v.warna as vehicle_warna,
               br.nama as brand_nama, m.nama as model_nama,
               pu.nama as passenger_nama, pu.foto_profil as passenger_foto
        FROM bookings b
        JOIN trips t ON b.trip_id = t.id
        JOIN cities oc ON t.origin_city_id = oc.id
        JOIN cities dc ON t.destination_city_id = dc.id
        JOIN drivers d ON t.driver_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN brands br ON v.brand_id = br.id
        JOIN models m ON v.model_id = m.id
        JOIN users pu ON b.passenger_id = pu.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        sendError('Booking tidak ditemukan', 404);
    }
    
    // Check if user is passenger or driver of this booking
    if ($booking['passenger_id'] != $auth['user_id'] && $booking['driver_user_id'] != $auth['user_id']) {
        sendError('Anda tidak memiliki akses ke booking ini', 403);
    }
    
    sendSuccess($booking);
}

/**
 * Create New Booking
 */
function createBooking($db) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    validateRequired($data, ['trip_id', 'seat_count', 'pickup_location']);
    
    $tripId = (int)$data['trip_id'];
    $seatCount = (int)$data['seat_count'];
    
    // Get trip details
    $stmt = $db->prepare("
        SELECT t.*, d.user_id as driver_user_id 
        FROM trips t
        JOIN drivers d ON t.driver_id = d.id
        WHERE t.id = ?
    ");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        sendError('Trip tidak ditemukan', 404);
    }
    
    // Cannot book own trip
    if ($trip['driver_user_id'] == $auth['user_id']) {
        sendError('Anda tidak dapat memesan trip Anda sendiri');
    }
    
    // Check trip status
    if (!in_array($trip['status'], ['menunggu', 'aktif'])) {
        sendError('Trip tidak tersedia untuk pemesanan');
    }
    
    // Check available seats
    if ($trip['available_seats'] < $seatCount) {
        sendError('Kursi tidak cukup. Tersedia: ' . $trip['available_seats'] . ' kursi');
    }
    
    // Check if already booked
    $stmt = $db->prepare("
        SELECT id FROM bookings 
        WHERE trip_id = ? AND passenger_id = ? AND booking_status NOT IN ('dibatalkan', 'ditolak')
    ");
    $stmt->execute([$tripId, $auth['user_id']]);
    if ($stmt->rowCount() > 0) {
        sendError('Anda sudah memesan trip ini');
    }
    
    // Calculate fare
    $fare = $trip['seat_price'] * $seatCount;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO bookings (
                trip_id, passenger_id, seat_count, pickup_location, 
                dropoff_location, fare, catatan, booking_status, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'belum_bayar')
        ");
        
        $stmt->execute([
            $tripId,
            $auth['user_id'],
            $seatCount,
            sanitize($data['pickup_location']),
            sanitize($data['dropoff_location'] ?? $trip['titik_tujuan']),
            $fare,
            sanitize($data['catatan'] ?? '')
        ]);
        
        $bookingId = $db->lastInsertId();
        
        sendSuccess([
            'booking_id' => $bookingId,
            'fare' => $fare
        ], 'Pemesanan berhasil dibuat. Menunggu konfirmasi driver.');
        
    } catch (Exception $e) {
        sendError('Gagal membuat pemesanan: ' . $e->getMessage(), 500);
    }
}

/**
 * Update Booking Status (Driver accepts/rejects)
 */
function updateBookingStatus($db, $bookingId) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    // Accept both 'status' and 'booking_status' keys
    $status = $data['booking_status'] ?? $data['status'] ?? null;
    
    if (!$status) {
        sendError('Status diperlukan');
    }
    
    $validStatuses = ['diterima', 'ditolak', 'dibatalkan'];
    if (!in_array($status, $validStatuses)) {
        sendError('Status tidak valid. Gunakan: diterima, ditolak, atau dibatalkan');
    }
    
    // Get booking with trip info
    $stmt = $db->prepare("
        SELECT b.*, t.available_seats, t.driver_id, d.user_id as driver_user_id
        FROM bookings b
        JOIN trips t ON b.trip_id = t.id
        JOIN drivers d ON t.driver_id = d.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        sendError('Booking tidak ditemukan', 404);
    }
    
    // Driver can accept/reject, passenger can cancel their own booking
    $isDriver = $booking['driver_user_id'] == $auth['user_id'];
    $isPassenger = $booking['passenger_id'] == $auth['user_id'];
    
    if (!$isDriver && !$isPassenger) {
        sendError('Anda tidak memiliki akses untuk mengubah status pemesanan ini', 403);
    }
    
    // Passenger can only cancel
    if ($isPassenger && !$isDriver && $status !== 'dibatalkan') {
        sendError('Anda hanya dapat membatalkan pemesanan', 403);
    }
    
    if ($booking['booking_status'] !== 'pending' && !$isPassenger) {
        sendError('Pemesanan sudah diproses sebelumnya');
    }
    
    try {
        $db->beginTransaction();
        
        // Update booking status
        $stmt = $db->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
        $stmt->execute([sanitize($status), $bookingId]);
        
        // If accepted, reduce available seats
        if ($status === 'diterima') {
            $newAvailable = $booking['available_seats'] - $booking['seat_count'];
            if ($newAvailable < 0) {
                $db->rollBack();
                sendError('Kursi tidak cukup');
            }
            
            $stmt = $db->prepare("UPDATE trips SET available_seats = ? WHERE id = ?");
            $stmt->execute([$newAvailable, $booking['trip_id']]);
        }
        
        $db->commit();
        
        $message = $data['status'] === 'diterima' 
            ? 'Pemesanan diterima' 
            : 'Pemesanan ditolak';
        
        sendSuccess(null, $message);
        
    } catch (Exception $e) {
        $db->rollBack();
        sendError('Gagal mengupdate status: ' . $e->getMessage(), 500);
    }
}

/**
 * Update Payment Status
 */
function updatePaymentStatus($db, $bookingId) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    validateRequired($data, ['payment_status']);
    
    $validStatuses = ['belum_bayar', 'menunggu_konfirmasi', 'berhasil', 'gagal', 'refund'];
    if (!in_array($data['payment_status'], $validStatuses)) {
        sendError('Status pembayaran tidak valid');
    }
    
    // Get booking info
    $stmt = $db->prepare("
        SELECT b.*, d.user_id as driver_user_id, t.status as trip_status
        FROM bookings b
        JOIN trips t ON b.trip_id = t.id
        JOIN drivers d ON t.driver_id = d.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        sendError('Booking tidak ditemukan', 404);
    }
    
    // Passenger can mark as 'menunggu_konfirmasi', Driver can confirm as 'berhasil' or 'gagal'
    $isPassenger = $booking['passenger_id'] == $auth['user_id'];
    $isDriver = $booking['driver_user_id'] == $auth['user_id'];
    
    if (!$isPassenger && !$isDriver) {
        sendError('Anda tidak memiliki akses ke booking ini', 403);
    }
    
    // Passenger can only update to 'menunggu_konfirmasi'
    if ($isPassenger && !$isDriver && $data['payment_status'] !== 'menunggu_konfirmasi') {
        sendError('Anda hanya dapat mengkonfirmasi pembayaran');
    }
    
    // Driver can confirm payment (berhasil/gagal) only if trip is completed and payment status is 'menunggu_konfirmasi'
    if ($isDriver && !$isPassenger && in_array($data['payment_status'], ['berhasil', 'gagal'])) {
        if ($booking['payment_status'] !== 'menunggu_konfirmasi') {
            sendError('Pembayaran harus dalam status menunggu konfirmasi terlebih dahulu');
        }
        if ($booking['trip_status'] !== 'selesai') {
            sendError('Konfirmasi pembayaran hanya dapat dilakukan setelah perjalanan selesai');
        }
    }
    
    $updates = ['payment_status = ?'];
    $params = [sanitize($data['payment_status'])];
    
    // Set payment method if provided
    if (isset($data['payment_method'])) {
        $updates[] = 'payment_method = ?';
        $params[] = sanitize($data['payment_method']);
    }
    
    // Set paid_at if payment successful
    if ($data['payment_status'] === 'berhasil') {
        $updates[] = 'paid_at = NOW()';
    }
    
    $params[] = $bookingId;
    
    $sql = "UPDATE bookings SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    sendSuccess(null, 'Status pembayaran berhasil diupdate');
}

/**
 * Cancel Booking (Passenger)
 */
function cancelBooking($db, $bookingId) {
    $auth = requireAuth();
    
    // Get booking info
    $stmt = $db->prepare("
        SELECT b.*, t.available_seats, d.user_id as driver_user_id
        FROM bookings b
        JOIN trips t ON b.trip_id = t.id
        JOIN drivers d ON t.driver_id = d.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        sendError('Booking tidak ditemukan', 404);
    }
    
    // Only passenger can cancel their booking
    if ($booking['passenger_id'] != $auth['user_id']) {
        sendError('Anda tidak dapat membatalkan pemesanan ini', 403);
    }
    
    // Can only cancel pending or accepted bookings
    if (!in_array($booking['booking_status'], ['pending', 'diterima'])) {
        sendError('Pemesanan tidak dapat dibatalkan');
    }
    
    try {
        $db->beginTransaction();
        
        // Update booking status
        $stmt = $db->prepare("UPDATE bookings SET booking_status = 'dibatalkan' WHERE id = ?");
        $stmt->execute([$bookingId]);
        
        // If was accepted, restore available seats
        if ($booking['booking_status'] === 'diterima') {
            $newAvailable = $booking['available_seats'] + $booking['seat_count'];
            $stmt = $db->prepare("UPDATE trips SET available_seats = ? WHERE id = ?");
            $stmt->execute([$newAvailable, $booking['trip_id']]);
        }
        
        $db->commit();
        
        sendSuccess(null, 'Pemesanan berhasil dibatalkan');
        
    } catch (Exception $e) {
        $db->rollBack();
        sendError('Gagal membatalkan pemesanan: ' . $e->getMessage(), 500);
    }
}
