<?php
/**
 * Authentication API
 * Nebeng - Ride Sharing Platform
 */

$database = new Database();
$db = $database->getConnection();

switch ($requestMethod) {
    case 'POST':
        if ($id === 'register') {
            handleRegister($db);
        } elseif ($id === 'login') {
            handleLogin($db);
        } else {
            sendError('Endpoint tidak valid', 404);
        }
        break;
    default:
        sendError('Method tidak diizinkan', 405);
}

/**
 * Handle User Registration
 */
function handleRegister($db) {
    $data = getRequestBody();
    
    // Validate required fields
    validateRequired($data, ['nama', 'email', 'password', 'nomor_hp']);
    
    // Sanitize input
    $nama = sanitize($data['nama']);
    $email = sanitize($data['email']);
    $password = $data['password'];
    $nomor_hp = sanitize($data['nomor_hp']);
    $alamat = sanitize($data['alamat'] ?? '');
    $role = sanitize($data['role'] ?? 'penumpang');
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Format email tidak valid');
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        sendError('Password minimal 6 karakter');
    }
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        sendError('Email sudah terdaftar');
    }
    
    // Hash password
    $hashedPassword = hashPassword($password);
    
    try {
        $db->beginTransaction();
        
        // Insert user
        $stmt = $db->prepare("
            INSERT INTO users (nama, email, password, nomor_hp, alamat, role) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nama, $email, $hashedPassword, $nomor_hp, $alamat, $role]);
        $userId = $db->lastInsertId();
        
        // If registering as driver, create driver record
        if ($role === 'driver' || $role === 'both') {
            $no_sim = sanitize($data['no_sim'] ?? '');
            if (empty($no_sim)) {
                $db->rollBack();
                sendError('Nomor SIM wajib diisi untuk driver');
            }
            
            $stmt = $db->prepare("
                INSERT INTO drivers (user_id, no_sim) VALUES (?, ?)
            ");
            $stmt->execute([$userId, $no_sim]);
        }
        
        $db->commit();
        
        // Generate token
        $token = generateToken($userId);
        
        sendSuccess([
            'user' => [
                'id' => $userId,
                'nama' => $nama,
                'email' => $email,
                'role' => $role
            ],
            'token' => $token
        ], 'Registrasi berhasil');
        
    } catch (Exception $e) {
        $db->rollBack();
        sendError('Gagal mendaftar: ' . $e->getMessage(), 500);
    }
}

/**
 * Handle User Login
 */
function handleLogin($db) {
    $data = getRequestBody();
    
    // Validate required fields
    validateRequired($data, ['email', 'password']);
    
    $email = sanitize($data['email']);
    $password = $data['password'];
    
    // Get user by email
    $stmt = $db->prepare("
        SELECT u.*, d.id as driver_id, d.rating, d.total_trips, d.is_verified
        FROM users u
        LEFT JOIN drivers d ON u.id = d.user_id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        sendError('Email atau password salah', 401);
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        sendError('Email atau password salah', 401);
    }
    
    // Generate token
    $token = generateToken($user['id']);
    
    // Remove password from response
    unset($user['password']);
    
    sendSuccess([
        'user' => $user,
        'token' => $token
    ], 'Login berhasil');
}
