<?php
/**
 * Vehicles API
 * Nebeng - Ride Sharing Platform
 */

$database = new Database();
$db = $database->getConnection();

switch ($requestMethod) {
    case 'GET':
        if ($id === 'my-vehicles') {
            getMyVehicles($db);
        } elseif ($id) {
            getVehicleById($db, $id);
        } else {
            sendError('Endpoint tidak valid', 404);
        }
        break;
    case 'POST':
        createVehicle($db);
        break;
    case 'PUT':
        if ($id) {
            updateVehicle($db, $id);
        } else {
            sendError('ID kendaraan diperlukan', 400);
        }
        break;
    case 'DELETE':
        if ($id) {
            deleteVehicle($db, $id);
        } else {
            sendError('ID kendaraan diperlukan', 400);
        }
        break;
    default:
        sendError('Method tidak diizinkan', 405);
}

/**
 * Get My Vehicles
 */
function getMyVehicles($db) {
    $auth = requireAuth();
    
    $stmt = $db->prepare("
        SELECT v.*, b.nama as brand_nama, m.nama as model_nama, m.kapasitas
        FROM vehicles v
        JOIN brands b ON v.brand_id = b.id
        JOIN models m ON v.model_id = m.id
        WHERE v.user_id = ?
        ORDER BY v.created_at DESC
    ");
    $stmt->execute([$auth['user_id']]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($vehicles);
}

/**
 * Get Vehicle by ID
 */
function getVehicleById($db, $vehicleId) {
    $stmt = $db->prepare("
        SELECT v.*, b.nama as brand_nama, m.nama as model_nama, m.kapasitas,
               u.nama as owner_nama
        FROM vehicles v
        JOIN brands b ON v.brand_id = b.id
        JOIN models m ON v.model_id = m.id
        JOIN users u ON v.user_id = u.id
        WHERE v.id = ?
    ");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vehicle) {
        sendError('Kendaraan tidak ditemukan', 404);
    }
    
    sendSuccess($vehicle);
}

/**
 * Create New Vehicle
 */
function createVehicle($db) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    validateRequired($data, ['brand_id', 'model_id', 'plate_number']);
    
    // Check if user is a driver
    $stmt = $db->prepare("SELECT id FROM drivers WHERE user_id = ?");
    $stmt->execute([$auth['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        sendError('Anda harus terdaftar sebagai driver untuk menambahkan kendaraan', 403);
    }
    
    // Check if plate number already exists
    $stmt = $db->prepare("SELECT id FROM vehicles WHERE plate_number = ?");
    $stmt->execute([strtoupper(sanitize($data['plate_number']))]);
    
    if ($stmt->rowCount() > 0) {
        sendError('Nomor plat sudah terdaftar');
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO vehicles (user_id, brand_id, model_id, plate_number, warna, tahun)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $auth['user_id'],
            (int)$data['brand_id'],
            (int)$data['model_id'],
            strtoupper(sanitize($data['plate_number'])),
            sanitize($data['warna'] ?? ''),
            (int)($data['tahun'] ?? date('Y'))
        ]);
        
        $vehicleId = $db->lastInsertId();
        
        sendSuccess(['vehicle_id' => $vehicleId], 'Kendaraan berhasil ditambahkan');
        
    } catch (Exception $e) {
        sendError('Gagal menambahkan kendaraan: ' . $e->getMessage(), 500);
    }
}

/**
 * Update Vehicle
 */
function updateVehicle($db, $vehicleId) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
    $stmt->execute([$vehicleId, $auth['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        sendError('Kendaraan tidak ditemukan atau bukan milik Anda', 403);
    }
    
    $allowedFields = ['brand_id', 'model_id', 'plate_number', 'warna', 'tahun', 'foto'];
    $updates = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            if ($field === 'plate_number') {
                $params[] = strtoupper(sanitize($data[$field]));
            } elseif (in_array($field, ['brand_id', 'model_id', 'tahun'])) {
                $params[] = (int)$data[$field];
            } else {
                $params[] = sanitize($data[$field]);
            }
        }
    }
    
    if (empty($updates)) {
        sendError('Tidak ada data yang diupdate');
    }
    
    $params[] = $vehicleId;
    
    $sql = "UPDATE vehicles SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    sendSuccess(null, 'Kendaraan berhasil diupdate');
}

/**
 * Delete Vehicle
 */
function deleteVehicle($db, $vehicleId) {
    $auth = requireAuth();
    
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
    $stmt->execute([$vehicleId, $auth['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        sendError('Kendaraan tidak ditemukan atau bukan milik Anda', 403);
    }
    
    // Check if vehicle is used in active trips
    $stmt = $db->prepare("
        SELECT id FROM trips 
        WHERE vehicle_id = ? AND status IN ('menunggu', 'aktif', 'dalam_perjalanan')
    ");
    $stmt->execute([$vehicleId]);
    
    if ($stmt->rowCount() > 0) {
        sendError('Kendaraan tidak dapat dihapus karena sedang digunakan dalam trip aktif');
    }
    
    $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    
    sendSuccess(null, 'Kendaraan berhasil dihapus');
}
