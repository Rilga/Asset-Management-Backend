<?php

require __DIR__ . '/../vendor/autoload.php';

/**
 * Laravel on Vercel expects the same request lifecycle as public/index.php.
 * This entrypoint restores the original API request path when Vercel
 * rewrites /api/:path* to this single function entrypoint.
 */

if (isset($_GET['__api_path'])) {
    $path = trim((string) $_GET['__api_path'], '/');
    $uri = '/api' . ($path === '' ? '' : '/' . $path);
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['PATH_INFO'] = $uri;
    $_SERVER['PHP_SELF'] = '/api/index.php';
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
    $_SERVER['QUERY_STRING'] = '';
}

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());
