<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\UserController;
use App\Http\Resources\UserResource;
use App\Models\Permission;
use App\Models\Role;
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

// No permission middleware here: UserPolicy decides per action and per record,
// which is what lets a user edit their own profile without users.update.
Route::apiResource('users', UserController::class)->middleware('auth:sanctum');

Route::put('/user/locale', [LocaleController::class, 'update'])
    ->middleware('auth:sanctum')
    ->name('user.locale');

// Feeds the role pickers and filters. Read-only reference data.
Route::get('/roles', fn () => Role::orderBy('id')->get(['id', 'name', 'label']))
    ->middleware(['auth:sanctum', 'permission:'.Permission::USERS_VIEW])
    ->name('roles.index');

require __DIR__.'/auth.php';
