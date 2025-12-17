<?php
/**
 * Cities API
 * Nebeng - Ride Sharing Platform
 */

$database = new Database();
$db = $database->getConnection();

switch ($requestMethod) {
    case 'GET':
        if ($id) {
            getCityById($db, $id);
        } else {
            getAllCities($db);
        }
        break;
    default:
        sendError('Method tidak diizinkan', 405);
}

/**
 * Get All Cities
 */
function getAllCities($db) {
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    
    if ($search) {
        $stmt = $db->prepare("SELECT * FROM cities WHERE nama LIKE ? ORDER BY nama ASC");
        $stmt->execute(["%$search%"]);
    } else {
        $stmt = $db->query("SELECT * FROM cities ORDER BY nama ASC");
    }
    
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($cities);
}

/**
 * Get City by ID
 */
function getCityById($db, $cityId) {
    $stmt = $db->prepare("SELECT * FROM cities WHERE id = ?");
    $stmt->execute([$cityId]);
    $city = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$city) {
        sendError('Kota tidak ditemukan', 404);
    }
    
    sendSuccess($city);
}
