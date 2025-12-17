<?php
/**
 * Reviews API
 * Nebeng - Ride Sharing Platform
 */

$database = new Database();
$db = $database->getConnection();

switch ($requestMethod) {
    case 'GET':
        if ($id === 'user' && $action) {
            getReviewsByUser($db, $action);
        } elseif ($id === 'booking' && $action) {
            getReviewByBooking($db, $action);
        } elseif ($id === 'my-review' && $action) {
            getMyReviewForBooking($db, $action);
        } elseif ($id) {
            getReviewById($db, $id);
        } else {
            sendError('Endpoint tidak valid', 404);
        }
        break;
    case 'POST':
        createReview($db);
        break;
    case 'PUT':
        if ($id) {
            updateReview($db, $id);
        } else {
            sendError('ID review diperlukan', 400);
        }
        break;
    default:
        sendError('Method tidak diizinkan', 405);
}

/**
 * Get Reviews by User ID
 */
function getReviewsByUser($db, $userId) {
    $stmt = $db->prepare("
        SELECT r.*, u.nama as reviewer_nama, u.foto_profil as reviewer_foto
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.reviewee_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$userId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate average rating
    $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE reviewee_id = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendSuccess([
        'reviews' => $reviews,
        'average_rating' => round($stats['avg_rating'] ?? 0, 2),
        'total_reviews' => (int)$stats['total']
    ]);
}

/**
 * Get Review by Booking ID (all reviews for a booking)
 */
function getReviewByBooking($db, $bookingId) {
    $stmt = $db->prepare("
        SELECT r.*, 
               u1.nama as reviewer_nama, u1.foto_profil as reviewer_foto,
               u2.nama as reviewee_nama
        FROM reviews r
        JOIN users u1 ON r.reviewer_id = u1.id
        JOIN users u2 ON r.reviewee_id = u2.id
        WHERE r.booking_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$bookingId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($reviews);
}

/**
 * Get My Review for a Booking
 */
function getMyReviewForBooking($db, $bookingId) {
    $auth = requireAuth();
    
    $stmt = $db->prepare("
        SELECT r.*, 
               u1.nama as reviewer_nama, u1.foto_profil as reviewer_foto,
               u2.nama as reviewee_nama
        FROM reviews r
        JOIN users u1 ON r.reviewer_id = u1.id
        JOIN users u2 ON r.reviewee_id = u2.id
        WHERE r.booking_id = ? AND r.reviewer_id = ?
    ");
    $stmt->execute([$bookingId, $auth['user_id']]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendSuccess($review ?: null);
}

/**
 * Get Review by ID
 */
function getReviewById($db, $reviewId) {
    $stmt = $db->prepare("
        SELECT r.*, 
               u1.nama as reviewer_nama, u1.foto_profil as reviewer_foto,
               u2.nama as reviewee_nama
        FROM reviews r
        JOIN users u1 ON r.reviewer_id = u1.id
        JOIN users u2 ON r.reviewee_id = u2.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reviewId]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        sendError('Review tidak ditemukan', 404);
    }
    
    sendSuccess($review);
}

/**
 * Create New Review
 */
function createReview($db) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    validateRequired($data, ['booking_id', 'rating']);
    
    $bookingId = (int)$data['booking_id'];
    $rating = (int)$data['rating'];
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        sendError('Rating harus antara 1-5');
    }
    
    // Get booking info
    $stmt = $db->prepare("
        SELECT b.*, d.user_id as driver_user_id
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
    
    // Only completed bookings can be reviewed
    if ($booking['booking_status'] !== 'selesai') {
        sendError('Hanya perjalanan yang sudah selesai yang dapat direview');
    }
    
    // Determine reviewer and reviewee
    $isPassenger = $booking['passenger_id'] == $auth['user_id'];
    $isDriver = $booking['driver_user_id'] == $auth['user_id'];
    
    if (!$isPassenger && !$isDriver) {
        sendError('Anda tidak dapat mereview booking ini', 403);
    }
    
    // Check if already reviewed
    $stmt = $db->prepare("SELECT id FROM reviews WHERE booking_id = ? AND reviewer_id = ?");
    $stmt->execute([$bookingId, $auth['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        sendError('Anda sudah memberikan review untuk perjalanan ini');
    }
    
    // Set reviewee (passenger reviews driver, driver reviews passenger)
    $revieweeId = $isPassenger ? $booking['driver_user_id'] : $booking['passenger_id'];
    
    try {
        $db->beginTransaction();
        
        // Insert review
        $stmt = $db->prepare("
            INSERT INTO reviews (booking_id, reviewer_id, reviewee_id, rating, komentar)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $bookingId,
            $auth['user_id'],
            $revieweeId,
            $rating,
            sanitize($data['komentar'] ?? '')
        ]);
        
        // If reviewing driver, update driver's average rating
        if ($isPassenger) {
            $stmt = $db->prepare("
                UPDATE drivers d
                SET rating = (
                    SELECT AVG(r.rating) 
                    FROM reviews r 
                    WHERE r.reviewee_id = d.user_id
                ),
                total_trips = total_trips + 1
                WHERE d.user_id = ?
            ");
            $stmt->execute([$revieweeId]);
        }
        
        $db->commit();
        
        sendSuccess(null, 'Review berhasil dikirim');
        
    } catch (Exception $e) {
        $db->rollBack();
        sendError('Gagal mengirim review: ' . $e->getMessage(), 500);
    }
}

/**
 * Update Review
 */
function updateReview($db, $reviewId) {
    $auth = requireAuth();
    $data = getRequestBody();
    
    // Get existing review
    $stmt = $db->prepare("SELECT * FROM reviews WHERE id = ?");
    $stmt->execute([$reviewId]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        sendError('Review tidak ditemukan', 404);
    }
    
    // Only reviewer can update their own review
    if ($review['reviewer_id'] != $auth['user_id']) {
        sendError('Anda tidak dapat mengubah review ini', 403);
    }
    
    $updates = [];
    $params = [];
    
    if (isset($data['rating'])) {
        $rating = (int)$data['rating'];
        if ($rating < 1 || $rating > 5) {
            sendError('Rating harus antara 1-5');
        }
        $updates[] = 'rating = ?';
        $params[] = $rating;
    }
    
    if (isset($data['komentar'])) {
        $updates[] = 'komentar = ?';
        $params[] = sanitize($data['komentar']);
    }
    
    if (empty($updates)) {
        sendError('Tidak ada data yang diupdate');
    }
    
    $params[] = $reviewId;
    
    try {
        $db->beginTransaction();
        
        $sql = "UPDATE reviews SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Update driver's average rating if reviewee is a driver
        $stmt = $db->prepare("
            UPDATE drivers d
            SET rating = (
                SELECT AVG(r.rating) 
                FROM reviews r 
                WHERE r.reviewee_id = d.user_id
            )
            WHERE d.user_id = ?
        ");
        $stmt->execute([$review['reviewee_id']]);
        
        $db->commit();
        
        sendSuccess(null, 'Review berhasil diupdate');
        
    } catch (Exception $e) {
        $db->rollBack();
        sendError('Gagal mengupdate review: ' . $e->getMessage(), 500);
    }
}
