<?php

namespace App\Support;

use App\Contracts\Loggable;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes activity log entries.
 *
 * Registered as a singleton so the per-request bookkeeping in $seen lives and
 * dies with the request. A static property would outlive it and leak between
 * tests, which all share one PHP process.
 */
class ActivityRecorder
{
    private const REDACTION = '[redacted]';

    /**
     * Attributes whose value never reaches the log — but whose name does.
     *
     * A password hash is still a password: writing one into a table that exists
     * to be read by administrators would hand out offline cracking material and
     * outlive every later rotation of the real column. Dropping the field
     * entirely would go too far the other way, because "someone changed this
     * account's password" is exactly the sort of thing an audit log is for.
     *
     * @var array<int, string>
     */
    private const REDACTED = ['password', 'two_factor_secret', 'two_factor_recovery_codes'];

    /**
     * Attributes that are dropped, name and all.
     *
     * These change on their own, without anyone deciding to change them.
     * remember_token rotates when a session is remembered; locale is written by
     * the SPA at first sign-in from the browser's own language setting. Both
     * would otherwise report "so-and-so edited themselves" after nearly every
     * sign-in, and a log that mostly reports itself is a log nobody reads.
     *
     * @var array<int, string>
     */
    private const IGNORED = ['created_at', 'updated_at', 'remember_token', 'locale'];

    /**
     * What has already been written this request, keyed by "event:type:id".
     *
     * @var array<string, true>
     */
    private array $seen = [];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $event, ?Model $subject = null, array $properties = [], ?User $causer = null): Activity
    {
        // Resolved per call, not injected: a singleton constructed once would
        // otherwise pin the first request's user and IP forever.
        $current = auth()->guard()->user();
        $causer ??= $current instanceof User ? $current : null;
        $request = request();

        if ($subject) {
            $this->seen[$this->keyFor($event, $subject)] = true;
        }

        return Activity::create([
            'event' => $event,
            // Only points at a row that is still there. Deleting your own
            // account fires this event after the row has gone, and the foreign
            // key would refuse the entry — losing the record of the deletion at
            // the exact moment it is worth having. The name is kept either way,
            // which is what makes the entry readable.
            'causer_id' => $causer?->exists ? $causer->getKey() : null,
            'causer_name' => $causer?->name,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? $this->labelFor($subject) : null,
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * Record a model's own created/updated/deleted event.
     */
    public function logModelEvent(string $event, Model $model): ?Activity
    {
        if ($event !== Activity::UPDATED) {
            return $this->log($event, $model, ['attributes' => $this->clean($model->getAttributes())]);
        }

        $changes = $this->changes($model);

        // Every attribute that moved was one of the ignored ones, so nobody
        // decided anything here and there is nothing to report.
        if ($changes === []) {
            return null;
        }

        return $this->log($event, $model, ['changed' => $changes]);
    }

    /**
     * Record an edit Eloquent could not see, unless this record already has an
     * entry from the same request.
     *
     * A product's text lives in product_translations, so changing only the body
     * leaves every column on `products` untouched and fires no model event at
     * all. Someone has to say that the product was edited. The guard is what
     * stops that someone from saying it twice when the product row changed too
     * and has already logged the real diff.
     */
    public function logUnseenUpdate(Model $model): ?Activity
    {
        if (isset($this->seen[$this->keyFor(Activity::UPDATED, $model)])) {
            return null;
        }

        return $this->log(Activity::UPDATED, $model, ['changed' => []]);
    }

    /**
     * What changed in this save, old value beside new.
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function changes(Model $model): array
    {
        $changes = [];

        foreach ($this->clean($model->getChanges()) as $attribute => $value) {
            $changes[$attribute] = [
                // Both sides, or the old value would leak what the new one hides.
                'from' => $value === self::REDACTION ? self::REDACTION : $model->getOriginal($attribute),
                'to' => $value,
            ];
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function clean(array $attributes): array
    {
        $cleaned = [];

        foreach ($attributes as $name => $value) {
            if (in_array($name, self::IGNORED, true)) {
                continue;
            }

            $cleaned[$name] = in_array($name, self::REDACTED, true) ? self::REDACTION : $value;
        }

        return $cleaned;
    }

    private function labelFor(Model $model): string
    {
        return $model instanceof Loggable
            ? $model->activityLabel()
            : $model->getMorphClass().' #'.$model->getKey();
    }

    /**
     * The event is part of the key on purpose. Keying on the record alone would
     * mean a record created earlier in the same request could never also be
     * reported as edited, which is a different statement about a different thing.
     */
    private function keyFor(string $event, Model $model): string
    {
        return $event.':'.$model->getMorphClass().':'.$model->getKey();
    }
}
