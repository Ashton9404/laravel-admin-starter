<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    /**
     * Number of days covered by the registrations trend.
     */
    private const TREND_DAYS = 30;

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'totals' => $this->totals(),
            'registrations' => $this->registrations(),
            'users_by_role' => $this->usersByRole(),
            'recent_users' => $this->recentUsers(),
            // Null rather than absent for a viewer without activity.view: the
            // panel that renders it is the same panel either way, and the SPA
            // should not have to guess why a key is missing.
            'recent_activity' => Gate::allows('viewAny', Activity::class)
                ? $this->recentActivity()
                : null,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(): array
    {
        return ActivityResource::collection(
            Activity::with(['causer', 'subject'])->latestFirst()->take(8)->get()
        )->resolve();
    }

    /**
     * @return array<string, int>
     */
    private function totals(): array
    {
        $verified = User::whereNotNull('email_verified_at')->count();

        return [
            'users' => $total = User::count(),
            'verified' => $verified,
            'unverified' => $total - $verified,
            'new_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
        ];
    }

    /**
     * Daily sign-ups for the trend line.
     *
     * Days with no sign-ups are filled with zero: leaving them out would make
     * the line skip straight from one busy day to the next and read as if the
     * quiet days never happened.
     *
     * @return array<int, array{date: string, total: int}>
     */
    private function registrations(): array
    {
        $from = now()->subDays(self::TREND_DAYS - 1)->startOfDay();

        $counts = User::query()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(CarbonPeriod::create($from, now()->endOfDay()))
            ->map(fn (Carbon $date) => [
                'date' => $key = $date->toDateString(),
                'total' => (int) ($counts[$key] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, label: string, total: int}>
     */
    private function usersByRole(): array
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role) => [
                'name' => $role->name,
                'label' => $role->label,
                'total' => $role->users_count,
            ]);

        $assigned = User::whereHas('roles')->count();
        $unassigned = User::count() - $assigned;

        if ($unassigned > 0) {
            $roles->push(['name' => 'none', 'label' => 'No role', 'total' => $unassigned]);
        }

        return $roles->all();
    }

    /**
     * @return array<int, array{id: int, name: string, email: string, roles: array<int, string>, created_at: string|null, verified: bool}>
     */
    private function recentUsers(): array
    {
        return User::query()
            ->with('roles')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('label')->all(),
                'verified' => $user->hasVerifiedEmail(),
                'created_at' => $user->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
