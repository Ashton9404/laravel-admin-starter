<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Unauthenticated smoke test used by the SPA shell to confirm that the
 * front-end can reach the API through Nginx.
 */
Route::get('/ping', fn () => [
    'message' => 'pong',
    'laravel' => 'Laravel '.Application::VERSION,
    'php' => PHP_VERSION,
]);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
