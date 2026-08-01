<?php

namespace App\Models\Concerns;

use App\Models\Activity;
use App\Support\ActivityRecorder;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create/update/delete on the model that uses it.
 *
 * Hooking model events rather than controllers means a change made from a
 * console command, a seeder or a future endpoint is logged too — the log
 * describes what happened to the data, not which controller was fashionable
 * when the write was made.
 *
 * The cost is the other side of the same coin: writes that bypass Eloquent,
 * such as a mass `update()` on the query builder, fire nothing. Where that is
 * the right tool anyway, call the recorder directly.
 *
 * @mixin Model
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn (self $model) => $model->recordActivity(Activity::CREATED));
        static::updated(fn (self $model) => $model->recordActivity(Activity::UPDATED));
        static::deleted(fn (self $model) => $model->recordActivity(Activity::DELETED));
    }

    public function recordActivity(string $event): ?Activity
    {
        return app(ActivityRecorder::class)->logModelEvent($event, $this);
    }

    /**
     * Overridden by models whose human-readable name is not `name`.
     */
    public function activityLabel(): string
    {
        return (string) ($this->getAttribute('name') ?? $this->getMorphClass().' #'.$this->getKey());
    }
}
