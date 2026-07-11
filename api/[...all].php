<?php

require __DIR__ . '/../vendor/autoload.php';

/**
 * Catch-all API entrypoint for Vercel PHP functions.
 * This file allows /api/* routes to reach Laravel with the original path intact.
 */

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());
