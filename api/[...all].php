<?php

require __DIR__ . '/../vendor/autoload.php';

/**
 * Catch-all API entrypoint for Vercel PHP functions.
 * This file allows /api/* routes to reach Laravel with the original path intact.
 */

$path = null;
if (isset($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} elseif (isset($_SERVER['REQUEST_URI'])) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: null;
}

if ($path !== null) {
    if (!str_starts_with($path, '/api')) {
        $path = '/api' . ($path === '/' ? '' : $path);
    }

    $_SERVER['PATH_INFO'] = $path;
    $_SERVER['REQUEST_URI'] = $path . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
    $_SERVER['PHP_SELF'] = '/api/[...all].php';
    $_SERVER['SCRIPT_NAME'] = '/api/[...all].php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/[...all].php';
}

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());
