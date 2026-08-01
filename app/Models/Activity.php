<?php

namespace App\Models;

use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One thing that happened, written once and never touched again.
 *
 * Deliberately not using LogsActivity itself: a log that logs its own writes
 * has no bottom.
 */
#[Fillable([
    'event',
    'causer_id',
    'causer_name',
    'subject_type',
    'subject_id',
    'subject_label',
    'properties',
    'ip_address',
])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    protected $table = 'activity_log';

    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const DELETED = 'deleted';

    public const LOGIN = 'login';

    public const LOGOUT = 'logout';

    public const LOGIN_FAILED = 'login_failed';

    public const REORDERED = 'reordered';

    /**
     * Every event the API will accept as a filter. Anything not listed here is
     * rejected at validation rather than silently returning nothing.
     *
     * @var array<int, string>
     */
    public const EVENTS = [
        self::CREATED,
        self::UPDATED,
        self::DELETED,
        self::LOGIN,
        self::LOGOUT,
        self::LOGIN_FAILED,
        self::REORDERED,
    ];

    /**
     * There is no updated_at: see the migration.
     */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * Resolves to null once the subject has been deleted, which is why
     * subject_label exists.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<Activity>  $query
     */
    public function scopeLatestFirst(Builder $query): void
    {
        // id breaks ties: entries written inside one request share a timestamp,
        // and without a tiebreak they come back in whatever order the database
        // happens to pick, so "most recent" would not be stable between calls.
        $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
