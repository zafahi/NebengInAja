<?php
// Configuration constants
define('SESSION_LIFETIME', 86400 * 7); // 7 days in seconds
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-secret-key-change-this-in-production');
