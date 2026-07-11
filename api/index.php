<?php

/**
 * Vercel executes this file for every API request. Its rewrite changes the
 * physical script to /api/index.php, so restore the original API URI before
 * Laravel builds its request.
 *
 * SCRIPT_NAME must remain rooted at /index.php. If it is set to
 * /api/index.php, Symfony treats /api as the application's base path and
 * Laravel tries to match /auth/login instead of /api/auth/login.
 */

$path = $_GET['path'] ?? $_GET['__api_path'] ?? null;
if (is_string($path)) {
    $path = '/'.trim($path, '/');
}

if (is_string($path)) {
    if (!str_starts_with($path, '/api')) {
        $path = '/api' . ($path === '/' ? '' : $path);
    }

    // `path` is only a Vercel routing parameter; it is not part of the API
    // request that Laravel should receive.
    unset($_GET['path'], $_GET['__api_path']);
    $query = http_build_query($_GET);

    $_SERVER['PATH_INFO'] = $path;
    $_SERVER['REQUEST_URI'] = $path . ($query !== '' ? '?'.$query : '');
    $_SERVER['QUERY_STRING'] = $query;
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__).'/public/index.php';
}

require dirname(__DIR__).'/public/index.php';
