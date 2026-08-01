<?php

use App\Http\Controllers\DashboardController;
use App\Http\Resources\UserResource;
use App\Models\Permission;
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

Route::get('/user', fn (Request $request) => UserResource::make(
    $request->user()->load('roles.permissions')
))->middleware('auth:sanctum')->name('user');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth:sanctum', 'permission:'.Permission::USERS_VIEW])
    ->name('dashboard');

require __DIR__.'/auth.php';
