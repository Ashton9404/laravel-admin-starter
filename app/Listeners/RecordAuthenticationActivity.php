<?php

namespace App\Listeners;

use App\Models\Activity;
use App\Models\User;
use App\Support\ActivityRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Turns Laravel's own authentication events into log entries.
 *
 * Listening to the framework's events rather than editing the login controller
 * means every route into the session — the SPA form today, a console command or
 * an SSO callback tomorrow — is covered without anyone remembering to add a
 * line. Logging out is worth recording for the same reason as logging in: a
 * session that ends is how you tell a normal day from a stolen laptop.
 *
 * Nothing registers these: Laravel discovers any handle* method in this
 * directory from the event it type-hints. Registering them by hand as well —
 * which is where this started — subscribes each one twice, and every sign-in
 * lands in the log two times over.
 */
class RecordAuthenticationActivity
{
    public function __construct(private readonly ActivityRecorder $recorder) {}

    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $this->recorder->log(Activity::LOGIN, causer: $user);
        }
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $this->recorder->log(Activity::LOGOUT, causer: $user);
        }
    }

    /**
     * A failed sign-in is recorded against the account someone was trying to get
     * into — the subject — and has no causer, because whoever it was has not
     * proved they are anybody. Attributing it to the account owner would read as
     * an accusation against the one person we know it probably was not.
     *
     * The submitted email is kept so a run of attempts on one account is visible.
     * The submitted password is not: it is very often a real password, just for
     * the wrong service, and writing it down would make this table worse than
     * the breach it is meant to help detect.
     */
    public function handleFailed(Failed $event): void
    {
        $this->recorder->log(
            Activity::LOGIN_FAILED,
            $event->user instanceof User ? $event->user : null,
            ['email' => $event->credentials['email'] ?? null],
        );
    }
}
