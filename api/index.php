<?php
/**
 * API Index - Router
 * Nebeng - Ride Sharing Platform
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers.php';

setCorsHeaders();

// Get request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Parse the URI
$basePath = '/nebenginaja/api';
$uri = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));
$uri = trim($uri, '/');
$uriParts = explode('/', $uri);

// Route the request
$endpoint = $uriParts[0] ?? '';
$id = $uriParts[1] ?? null;
$action = $uriParts[2] ?? null;

// Route to appropriate handler
switch ($endpoint) {
    case 'auth':
        require_once __DIR__ . '/auth.php';
        break;
    case 'users':
        require_once __DIR__ . '/users.php';
        break;
    case 'trips':
        require_once __DIR__ . '/trips.php';
        break;
    case 'bookings':
        require_once __DIR__ . '/bookings.php';
        break;
    case 'vehicles':
        require_once __DIR__ . '/vehicles.php';
        break;
    case 'cities':
        require_once __DIR__ . '/cities.php';
        break;
    case 'brands':
        require_once __DIR__ . '/brands.php';
        break;
    case 'reviews':
        require_once __DIR__ . '/reviews.php';
        break;
    case '':
        sendSuccess([
            'name' => 'Nebeng API',
            'version' => '1.0.0',
            'description' => 'API untuk platform ride-sharing Nebeng'
        ], 'Welcome to Nebeng API');
        break;
    default:
        sendError('Endpoint tidak ditemukan', 404);
}
