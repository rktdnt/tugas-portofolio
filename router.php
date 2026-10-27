<?php
/**
 * Router untuk Development Server Lokal
 * Jalankan: php -S localhost:8000 router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Sajikan berkas statis langsung jika ada di filesystem
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Routing URL bersih ke folder api/
if ($uri === '/admin' || $uri === '/admin.php') {
    require __DIR__ . '/api/admin.php';
} elseif ($uri === '/kontak' || $uri === '/kontak.php') {
    require __DIR__ . '/api/kontak.php';
} elseif ($uri === '/proyek' || $uri === '/proyek.php') {
    require __DIR__ . '/api/proyek.php';
} else {
    require __DIR__ . '/api/index.php';
}
