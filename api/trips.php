<?php
/**
 * Trips API
 * Nebeng - Ride Sharing Platform
 */

$database = new Database();
$db = $database->getConnection();

switch ($requestMethod) {
    case 'GET':
        if ($id === 'search') {
            searchTrips($db);
        } elseif ($id === 'my-trips') {
            getMyTrips($db);
        } elseif ($id && $action === 'bookings') {
            getTripBookings($db, $id);
        } elseif ($id) {
            getTripById($db, $id);
        } else {
            getAllTrips($db);
        }
        break;
    case 'POST':
        createTrip($db);
        break;
    case 'PUT':
        if ($id && $action === 'status') {
            updateTripStatus($db, $id);
        } elseif ($id) {
            updateTrip($db, $id);
        } else {
            sendError('ID trip diperlukan', 400);
        }
        break;
    case 'DELETE':
        if ($id) {
            deleteTrip($db, $id);
        } else {
            sendError('ID trip diperlukan', 400);
        }
        break;
    default:
        sendError('Method tidak diizinkan', 405);
}

/**
 * Get All Active Trips
 */
function getAllTrips($db) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    $stmt = $db->prepare("
        SELECT t.*, 
               u.nama as driver_nama, u.foto_profil as driver_foto,
               d.rating as driver_rating, d.total_trips as driver_total_trips,
               oc.nama as origin_city, dc.nama as destination_city,
               v.plate_number, b.nama as brand_nama, m.nama as model_nama
        FROM trips t
        JOIN drivers d ON t.driver_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN cities oc ON t.origin_city_id = oc.id
        JOIN cities dc ON t.destination_city_id = dc.id
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN brands b ON v.brand_id = b.id
        JOIN models m ON v.model_id = m.id
        WHERE t.status IN ('menunggu', 'aktif')
        AND t.waktu_keberangkatan > NOW()
        ORDER BY t.waktu_keberangkatan ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $stmt = $db->query("
        SELECT COUNT(*) as total FROM trips 
        WHERE status IN ('menunggu', 'aktif') AND waktu_keberangkatan > NOW()
    ");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    sendSuccess([
        'trips' => $trips,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Search Trips
 */
function searchTrips($db) {
    $origin = isset($_GET['origin']) ? (int)$_GET['origin'] : null;
    $destination = isset($_GET['destination']) ? (int)$_GET['destination'] : null;
    $date = isset($_GET['date']) ? sanitize($_GET['date']) : null;
    $seats = isset($_GET['seats']) ? (int)$_GET['seats'] : 1;
    
    $conditions = ["t.status IN ('menunggu', 'aktif')", "t.waktu_keberangkatan > NOW()"];
    $params = [];
    
    if ($origin) {
        $conditions[] = "t.origin_city_id = ?";
        $params[] = $origin;
    }
    
    if ($destination) {
        $conditions[] = "t.destination_city_id = ?";
        $params[] = $destination;
    }
    
    if ($date) {
        $conditions[] = "DATE(t.waktu_keberangkatan) = ?";
        $params[] = $date;
    }
    
    if ($seats) {
        $conditions[] = "t.available_seats >= ?";
        $params[] = $seats;
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    $stmt = $db->prepare("
        SELECT t.*, 
               u.nama as driver_nama, u.foto_profil as driver_foto,
               d.rating as driver_rating,
               oc.nama as origin_city, dc.nama as destination_city,
               v.plate_number, b.nama as brand_nama, m.nama as model_nama
        FROM trips t
        JOIN drivers d ON t.driver_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN cities oc ON t.origin_city_id = oc.id
        JOIN cities dc ON t.destination_city_id = dc.id
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN brands b ON v.brand_id = b.id
        JOIN models m ON v.model_id = m.id
        WHERE $whereClause
        ORDER BY t.waktu_keberangkatan ASC
    ");
    $stmt->execute($params);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($trips);
}

/**
 * Get My Trips (Driver)
 */
function getMyTrips($db) {
    $auth = requireAuth();
    
    // Get driver_id
    $stmt = $db->prepare("SELECT id FROM drivers WHERE user_id = ?");
    $stmt->execute([$auth['user_id']]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$driver) {
        sendError('Anda bukan driver', 403);
    }
    
    $status = isset($_GET['status']) ? sanitize($_GET['status']) : null;
    
    $conditions = ["t.driver_id = ?"];
    $params = [$driver['id']];
    
    if ($status) {
        $conditions[] = "t.status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    $stmt = $db->prepare("
        SELECT t.*, 
               oc.nama as origin_city, dc.nama as destination_city,
               v.plate_number, b.nama as brand_nama, m.nama as model_nama,
               (SELECT COUNT(*) FROM bookings b2 WHERE b2.trip_id = t.id AND b2.booking_status IN ('diterima', 'dalam_perjalanan', 'selesai')) as confirmed_bookings,
               (SELECT COUNT(*) FROM bookings b3 WHERE b3.trip_id = t.id AND b3.booking_status NOT IN ('ditolak', 'dibatalkan')) as total_bookings
        FROM trips t
        JOIN cities oc ON t.origin_city_id = oc.id
        JOIN cities dc ON t.destination_city_id = dc.id
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN brands b ON v.brand_id = b.id
        JOIN models m ON v.model_id = m.id
        WHERE $whereClause
        ORDER BY t.created_at DESC
    ");
    $stmt->execute($params);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($trips);
}

/**
 * Get Trip by ID
 */
function getTripById($db, $tripId) {
    $stmt = $db->prepare("
        SELECT t.*, 
               u.id as driver_user_id, u.nama as driver_nama, u.foto_profil as driver_foto, 
               u.nomor_hp as driver_hp,
               d.rating as driver_rating, d.total_trips as driver_total_trips, d.is_verified,
               oc.nama as origin_city, dc.nama as destination_city,
               v.plate_number, v.warna as vehicle_warna, v.tahun as vehicle_tahun,
               b.nama as brand_nama, m.nama as model_nama, m.kapasitas
        FROM trips t
        JOIN drivers d ON t.driver_id = d.id
        JOIN users u ON d.user_id = u.id
        JOIN cities oc ON t.origin_city_id = oc.id
        JOIN cities dc ON t.destination_city_id = dc.id
        JOIN vehicles v ON t.vehicle_id = v.id
        JOIN brands b ON v.brand_id = b.id
        JOIN models m ON v.model_id = m.id
        WHERE t.id = ?
    ");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        sendError('Trip tidak ditemukan', 404);
    }
    
    sendSuccess($trip);
}

/**
 * Get Bookings for a Trip (only for trip owner)
 */
function getTripBookings($db, $tripId) {
    $auth = requireAuth();
    
    // Verify trip exists and user is the owner
    $stmt = $db->prepare("
        SELECT t.id, d.user_id 
        FROM trips t 
        JOIN drivers d ON t.driver_id = d.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        sendError('Trip tidak ditemukan', 404);
    }
    
    if ($trip['user_id'] != $auth['user_id']) {
        sendError('Anda tidak memiliki akses ke data ini', 403);
    }
    
    // Get all bookings for this trip
    $stmt = $db->prepare("
        SELECT b.*, 
               u.nama as passenger_nama, 
               u.foto_profil as passenger_foto,
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
 * Create New Trip
 */
function createTrip($db) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    validateRequired($data, [
        'vehicle_id', 'origin_city_id', 'destination_city_id',
        'titik_jemput', 'titik_tujuan', 'waktu_keberangkatan',
        'seat_price', 'max_passenger'
    ]);
    
    // Get driver_id
    $stmt = $db->prepare("SELECT id FROM drivers WHERE user_id = ?");
    $stmt->execute([$auth['user_id']]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$driver) {
        sendError('Anda harus terdaftar sebagai driver terlebih dahulu', 403);
    }
    
    // Verify vehicle ownership
    $stmt = $db->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['vehicle_id'], $auth['user_id']]);
    if ($stmt->rowCount() === 0) {
        sendError('Kendaraan tidak ditemukan atau bukan milik Anda', 403);
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO trips (
                driver_id, vehicle_id, origin_city_id, destination_city_id,
                titik_jemput, titik_tujuan, waktu_keberangkatan,
                seat_price, max_passenger, available_seats, deskripsi, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
        ");
        
        $maxPassenger = (int)$data['max_passenger'];
        
        $stmt->execute([
            $driver['id'],
            $data['vehicle_id'],
            $data['origin_city_id'],
            $data['destination_city_id'],
            sanitize($data['titik_jemput']),
            sanitize($data['titik_tujuan']),
            $data['waktu_keberangkatan'],
            (int)$data['seat_price'],
            $maxPassenger,
            $maxPassenger, // available_seats = max_passenger initially
            sanitize($data['deskripsi'] ?? '')
        ]);
        
        $tripId = $db->lastInsertId();
        
        sendSuccess(['trip_id' => $tripId], 'Trip berhasil dibuat');
        
    } catch (Exception $e) {
        sendError('Gagal membuat trip: ' . $e->getMessage(), 500);
    }
}

/**
 * Update Trip
 */
function updateTrip($db, $tripId) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    // Verify ownership
    $stmt = $db->prepare("
        SELECT t.id FROM trips t
        JOIN drivers d ON t.driver_id = d.id
        WHERE t.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$tripId, $auth['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        sendError('Trip tidak ditemukan atau bukan milik Anda', 403);
    }
    
    $allowedFields = ['titik_jemput', 'titik_tujuan', 'waktu_keberangkatan', 'seat_price', 'deskripsi'];
    $updates = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = is_string($data[$field]) ? sanitize($data[$field]) : $data[$field];
        }
    }
    
    if (empty($updates)) {
        sendError('Tidak ada data yang diupdate');
    }
    
    $params[] = $tripId;
    
    $sql = "UPDATE trips SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    sendSuccess(null, 'Trip berhasil diupdate');
}

/**
 * Update Trip Status
 */
function updateTripStatus($db, $tripId) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    validateRequired($data, ['status']);
    
    $validStatuses = ['menunggu', 'aktif', 'dalam_perjalanan', 'selesai', 'dibatalkan'];
    if (!in_array($data['status'], $validStatuses)) {
        sendError('Status tidak valid');
    }
    
    // Verify ownership
    $stmt = $db->prepare("
        SELECT t.id FROM trips t
        JOIN drivers d ON t.driver_id = d.id
        WHERE t.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$tripId, $auth['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        sendError('Trip tidak ditemukan atau bukan milik Anda', 403);
    }
    
    $stmt = $db->prepare("UPDATE trips SET status = ? WHERE id = ?");
    $stmt->execute([sanitize($data['status']), $tripId]);
    
    // If trip is completed or cancelled, update related bookings
    if ($data['status'] === 'selesai') {
        $stmt = $db->prepare("
            UPDATE bookings SET booking_status = 'selesai' 
            WHERE trip_id = ? AND booking_status = 'dalam_perjalanan'
        ");
        $stmt->execute([$tripId]);
    } elseif ($data['status'] === 'dibatalkan') {
        $stmt = $db->prepare("
            UPDATE bookings SET booking_status = 'dibatalkan' 
            WHERE trip_id = ? AND booking_status IN ('pending', 'diterima')
        ");
        $stmt->execute([$tripId]);
    } elseif ($data['status'] === 'dalam_perjalanan') {
        $stmt = $db->prepare("
            UPDATE bookings SET booking_status = 'dalam_perjalanan' 
            WHERE trip_id = ? AND booking_status = 'diterima'
        ");
        $stmt->execute([$tripId]);
    }
    
    sendSuccess(null, 'Status trip berhasil diupdate');
}

/**
 * Delete Trip
 */
function deleteTrip($db, $tripId) {
    $auth = requireAuth();
    
    // Verify ownership and check if can be deleted
    $stmt = $db->prepare("
        SELECT t.id, t.status FROM trips t
        JOIN drivers d ON t.driver_id = d.id
        WHERE t.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$tripId, $auth['user_id']]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        sendError('Trip tidak ditemukan atau bukan milik Anda', 403);
    }
    
    if (!in_array($trip['status'], ['menunggu', 'aktif'])) {
        sendError('Trip tidak dapat dihapus karena sudah dalam perjalanan atau selesai');
    }
    
    // Check if there are accepted bookings
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE trip_id = ? AND booking_status = 'diterima'");
    $stmt->execute([$tripId]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        sendError('Trip tidak dapat dihapus karena sudah ada pemesanan yang diterima');
    }
    
    $stmt = $db->prepare("DELETE FROM trips WHERE id = ?");
    $stmt->execute([$tripId]);
    
    sendSuccess(null, 'Trip berhasil dihapus');
}
