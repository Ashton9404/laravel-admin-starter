<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

/**
 * index is the only action, and that is the whole design: nothing in the
 * application may write to this table through HTTP, so there is no route to
 * forget to protect.
 */
class ActivityController extends Controller implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [new Middleware('can:viewAny,'.Activity::class)];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'event' => ['sometimes', 'nullable', Rule::in(Activity::EVENTS)],
            // Validated against the morph map rather than a free string: an
            // unknown type would otherwise be a silent empty result set.
            'subject_type' => ['sometimes', 'nullable', Rule::in(array_keys(Relation::morphMap()))],
            'causer_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'per_page' => ['sometimes', 'integer', 'min:10', 'max:100'],
        ]);

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->string('event')))
            ->when($request->filled('subject_type'), fn ($query) => $query->where('subject_type', $request->string('subject_type')))
            ->when($request->filled('causer_id'), fn ($query) => $query->where('causer_id', $request->integer('causer_id')))
            ->latestFirst()
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return ActivityResource::collection($activities);
    }
}
