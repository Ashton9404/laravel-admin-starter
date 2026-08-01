<?php

use Illuminate\Support\Facades\Route;

/**
 * Every non-API URL renders the same Blade shell; Vue Router takes over
 * from there. Keep this route last so real server-side routes still win.
 */
Route::get('/{any?}', fn () => view('app'))
    ->where('any', '^(?!api|sanctum|storage|up).*$');
