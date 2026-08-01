<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    /**
     * Register a new user and log them straight in.
     *
     * The Registered event is what triggers the verification email; the account
     * is usable immediately, but routes behind the "verified" middleware are not.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->only('name', 'email', 'password'));

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return UserResource::make($user)
            ->response()
            ->setStatusCode(201);
    }
}
