<?php

require __DIR__ . '/../vendor/autoload.php';

/**
 * Laravel on Vercel expects the same request lifecycle as public/index.php.
 * This entrypoint simply bootstraps Laravel and forwards all requests.
 */

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());
