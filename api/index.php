<?php

require __DIR__ . '/../vendor/autoload.php';

/**
 * Laravel on Vercel expects the same request lifecycle as public/index.php.
 * This entrypoint restores the original API request path when Vercel
 * rewrites /api/:path* to this single function entrypoint.
 */

$path = null;
if (isset($_GET['path']) && is_string($_GET['path'])) {
    $path = '/' . trim($_GET['path'], '/');
}

if ($path !== null) {
    if (!str_starts_with($path, '/api')) {
        $path = '/api' . ($path === '/' ? '' : $path);
    }

    $_SERVER['PATH_INFO'] = $path;
    $_SERVER['REQUEST_URI'] = $path . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
    $_SERVER['PHP_SELF'] = '/api/index.php';
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
}

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());
