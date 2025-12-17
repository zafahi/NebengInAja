<?php
/**
 * Users API
 * Nebeng - Ride Sharing Platform
 */

$database = new Database();
$db = $database->getConnection();

switch ($requestMethod) {
    case 'GET':
        if ($id === 'me') {
            getMyProfile($db);
        } elseif ($id) {
            getUserById($db, $id);
        } else {
            sendError('Endpoint tidak valid', 404);
        }
        break;
    case 'PUT':
        if ($id === 'me') {
            updateMyProfile($db);
        } elseif ($id && $action === 'become-driver') {
            becomeDriver($db, $id);
        } else {
            sendError('Endpoint tidak valid', 404);
        }
        break;
    default:
        sendError('Method tidak diizinkan', 405);
}

/**
 * Get Current User Profile
 */
function getMyProfile($db) {
    $auth = requireAuth();
    
    $stmt = $db->prepare("
        SELECT u.*, d.id as driver_id, d.no_sim, d.rating, d.total_trips, d.is_verified
        FROM users u
        LEFT JOIN drivers d ON u.id = d.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$auth['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User tidak ditemukan', 404);
    }
    
    unset($user['password']);
    
    // Get user's vehicles if driver
    if ($user['driver_id']) {
        $stmt = $db->prepare("
            SELECT v.*, b.nama as brand_nama, m.nama as model_nama, m.kapasitas
            FROM vehicles v
            JOIN brands b ON v.brand_id = b.id
            JOIN models m ON v.model_id = m.id
            WHERE v.user_id = ?
        ");
        $stmt->execute([$auth['user_id']]);
        $user['vehicles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    sendSuccess($user);
}

/**
 * Get User by ID (public profile)
 */
function getUserById($db, $userId) {
    $stmt = $db->prepare("
        SELECT u.id, u.nama, u.foto_profil, u.role, u.created_at,
               d.rating, d.total_trips, d.is_verified
        FROM users u
        LEFT JOIN drivers d ON u.id = d.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User tidak ditemukan', 404);
    }
    
    // Get reviews for this user
    $stmt = $db->prepare("
        SELECT r.*, u.nama as reviewer_nama
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.reviewee_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $user['recent_reviews'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($user);
}

/**
 * Update Current User Profile
 */
function updateMyProfile($db) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    $allowedFields = ['nama', 'alamat', 'nomor_hp', 'foto_profil'];
    $updates = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = sanitize($data[$field]);
        }
    }
    
    if (empty($updates)) {
        sendError('Tidak ada data yang diupdate');
    }
    
    $params[] = $auth['user_id'];
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    // Return updated profile
    getMyProfile($db);
}

/**
 * Upgrade User to Driver
 */
function becomeDriver($db, $userId) {
    $auth = requireAuth();
    
    if ($auth['user_id'] != $userId) {
        sendError('Unauthorized', 403);
    }
    
    $data = getRequestBody();
    validateRequired($data, ['no_sim']);
    
    // Check if already a driver
    $stmt = $db->prepare("SELECT id FROM drivers WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() > 0) {
        sendError('Anda sudah terdaftar sebagai driver');
    }
    
    try {
        $db->beginTransaction();
        
        // Create driver record
        $stmt = $db->prepare("INSERT INTO drivers (user_id, no_sim) VALUES (?, ?)");
        $stmt->execute([$userId, sanitize($data['no_sim'])]);
        
        // Update user role
        $stmt = $db->prepare("UPDATE users SET role = 'both' WHERE id = ? AND role = 'penumpang'");
        $stmt->execute([$userId]);
        
        $db->commit();
        
        sendSuccess(null, 'Berhasil menjadi driver');
        
    } catch (Exception $e) {
        $db->rollBack();
        sendError('Gagal mendaftar sebagai driver: ' . $e->getMessage(), 500);
    }
}
