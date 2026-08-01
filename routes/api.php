<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCoverController;
use App\Http\Controllers\PublicProductController;
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

/**
 * The public website. No authentication, and deliberately declared before every
 * guarded route so it is obvious at a glance which endpoints are open.
 *
 * SetLocale still runs here, so Accept-Language picks the translation an
 * anonymous visitor sees.
 */
Route::prefix('public')->name('public.')->group(function () {
    Route::get('products', [PublicProductController::class, 'index'])->name('products.index');
    Route::get('products/{slug}', [PublicProductController::class, 'show'])->name('products.show');
});

Route::get('/user', fn (Request $request) => UserResource::make(
    $request->user()->load('roles.permissions')
))->middleware('auth:sanctum')->name('user');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth:sanctum', 'permission:'.Permission::USERS_VIEW])
    ->name('dashboard');

// No permission middleware here: UserPolicy decides per action and per record,
// which is what lets a user edit their own profile without users.update.
Route::apiResource('users', UserController::class)->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Declared before the resource so "reorder" is not swallowed by the
    // {product} wildcard on /api/products/{product}.
    Route::post('products/reorder', [ProductController::class, 'reorder'])->name('products.reorder');

    Route::post('products/{product}/cover', [ProductCoverController::class, 'store'])->name('products.cover.store');
    Route::delete('products/{product}/cover', [ProductCoverController::class, 'destroy'])->name('products.cover.destroy');

    Route::apiResource('products', ProductController::class);

    // Only these three: media is upload-and-delete, never edited in place.
    Route::apiResource('media', MediaController::class)->only(['index', 'store', 'destroy']);

    // Read-only on purpose. Nothing writes to the log over HTTP, so there is no
    // write route that could later be left unguarded.
    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
});

Route::put('/user/locale', [LocaleController::class, 'update'])
    ->middleware('auth:sanctum')
    ->name('user.locale');

// Feeds the role pickers and filters. Read-only reference data.
Route::get('/roles', fn () => Role::orderBy('id')->get(['id', 'name', 'label']))
    ->middleware(['auth:sanctum', 'permission:'.Permission::USERS_VIEW])
    ->name('roles.index');

require __DIR__.'/auth.php';
