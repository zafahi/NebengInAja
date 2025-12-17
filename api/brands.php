<?php
/**
 * Brands API
 * Nebeng - Ride Sharing Platform
 */

$database = new Database();
$db = $database->getConnection();

switch ($requestMethod) {
    case 'GET':
        if ($id === 'models' && $action) {
            getModelsByBrand($db, $action);
        } elseif ($id) {
            getBrandById($db, $id);
        } else {
            getAllBrands($db);
        }
        break;
    default:
        sendError('Method tidak diizinkan', 405);
}

/**
 * Get All Brands
 */
function getAllBrands($db) {
    $stmt = $db->query("SELECT * FROM brands ORDER BY nama ASC");
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($brands);
}

/**
 * Get Brand by ID
 */
function getBrandById($db, $brandId) {
    $stmt = $db->prepare("SELECT * FROM brands WHERE id = ?");
    $stmt->execute([$brandId]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$brand) {
        sendError('Brand tidak ditemukan', 404);
    }
    
    // Get models for this brand
    $stmt = $db->prepare("SELECT * FROM models WHERE brand_id = ? ORDER BY nama ASC");
    $stmt->execute([$brandId]);
    $brand['models'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($brand);
}

/**
 * Get Models by Brand ID
 */
function getModelsByBrand($db, $brandId) {
    $stmt = $db->prepare("SELECT * FROM models WHERE brand_id = ? ORDER BY nama ASC");
    $stmt->execute([$brandId]);
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendSuccess($models);
}
