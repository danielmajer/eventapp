<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Event Manager backend is running'], 200);
});

// Named route for auth redirects (API doesn't use this, but Laravel expects it)
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthorized. Please login via API.'], 401);
})->name('login');
