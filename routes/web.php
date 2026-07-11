<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/api/documentation');
});

require __DIR__.'/auth.php';
