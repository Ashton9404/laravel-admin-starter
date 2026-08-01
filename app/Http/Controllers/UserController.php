<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class UserController extends Controller implements HasMiddleware
{
    /**
     * Maps every action onto the matching UserPolicy method.
     *
     * Declared as middleware rather than checked inside the actions so the
     * policy runs *before* validation — otherwise a caller who is not allowed
     * to touch a record would still learn which fields it rejects.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.User::class, only: ['index']),
            new Middleware('can:create,'.User::class, only: ['store']),
            new Middleware('can:view,user', only: ['show']),
            new Middleware('can:update,user', only: ['update']),
            new Middleware('can:delete,user', only: ['destroy']),
        ];
    }

    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->with('roles.permissions')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';

                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role'), fn ($query) => $query->whereHas(
                'roles',
                fn ($q) => $q->where('name', $request->string('role'))
            ))
            ->when($request->has('verified') && $request->input('verified') !== null, fn ($query) => $request->boolean('verified')
                    ? $query->whereNotNull('email_verified_at')
                    : $query->whereNull('email_verified_at'))
            ->orderBy(
                $request->input('sort', 'created_at'),
                $request->input('direction', 'desc'),
            )
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        return UserResource::make($user->load('roles.permissions'));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->safe()->only('name', 'email', 'password'));

            $this->syncRoles($user, $request->input('roles'));

            if ($request->boolean('email_verified')) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return $user;
        });

        // Only chase a verification email when the account still needs one.
        if (! $user->hasVerifiedEmail()) {
            event(new Registered($user));
        }

        return UserResource::make($user->load('roles.permissions'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        DB::transaction(function () use ($request, $user) {
            $user->fill($request->safe()->only('name', 'email'));

            if ($request->filled('password')) {
                $user->password = $request->string('password');
            }

            // Changing the address means it is unproven again.
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($request->has('roles')) {
                $this->syncRoles($user, $request->input('roles'));
            }
        });

        return UserResource::make($user->fresh()->load('roles.permissions'));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => __('User deleted.')]);
    }

    /**
     * @param  array<int, string>|null  $names
     */
    private function syncRoles(User $user, ?array $names): void
    {
        $user->roles()->sync(Role::whereIn('name', $names ?? [])->pluck('id'));
        $user->forgetCachedPermissions();
    }
}
