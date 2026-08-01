<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Log the user in and issue a fresh session.
     */
    public function store(LoginRequest $request): UserResource
    {
        $request->authenticate();

        // Prevents session fixation: the pre-login session id can no longer be
        // replayed by whoever may have planted it.
        $request->session()->regenerate();

        // Roles drive what the SPA renders, so they have to be in the very
        // first response — otherwise the UI stays permission-blind until the
        // next full page load refetches /api/user.
        return UserResource::make($request->user()->load('roles.permissions'));
    }

    /**
     * Log the user out and destroy the session entirely.
     */
    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => __('Logged out.')]);
    }
}
