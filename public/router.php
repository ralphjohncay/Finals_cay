<?php

/**
 * Router for PHP's built-in web server (Railway / Docker).
 * Without this, only "/" works and every Symfony route returns 404.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.php';
