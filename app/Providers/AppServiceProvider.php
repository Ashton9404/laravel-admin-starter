<?php

namespace App\Providers;

use App\Models\Media;
use App\Models\Product;
use App\Models\User;
use App\Support\ActivityRecorder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton so the recorder's per-request bookkeeping is scoped to the
        // request, and rebuilt from scratch for the next one.
        $this->app->singleton(ActivityRecorder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePasswordPolicy();
        $this->configureResetPasswordUrl();
        $this->configureAuthorization();
        $this->configureMorphMap();
    }

    /**
     * Store short aliases in polymorphic columns instead of class names.
     *
     * The default writes "App\Models\Product" into every activity_log row, which
     * quietly makes the namespace part of the database schema: renaming or moving
     * the class then breaks history that has already been written. enforceMorphMap
     * also refuses to guess, so a new loggable model has to be declared here
     * rather than leaking its namespace on first use.
     */
    private function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'product' => Product::class,
            'media' => Media::class,
        ]);
    }

    /**
     * Administrators bypass every gate and policy — except on their own account.
     *
     * Two deliberate choices here:
     *
     * Returning null rather than false for non-admins means "no opinion", so the
     * normal policy still gets to decide. Returning false would deny everyone.
     *
     * Withholding the bypass when the target *is* the acting user keeps rules
     * like "nobody may delete themselves" alive for administrators. Those rules
     * exist precisely to stop the most privileged account from locking everybody
     * out, so an admin bypass would disable them exactly where they matter.
     */
    private function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments = []) {
            $target = $arguments[0] ?? null;

            if ($target instanceof User && $user->is($target)) {
                return null;
            }

            return $user->isAdmin() ? true : null;
        });
    }

    /**
     * A single place to tighten password rules for the whole application;
     * every `Password::defaults()` rule picks this up.
     */
    private function configurePasswordPolicy(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }

    /**
     * The reset link is emailed to the user and must land on the SPA route that
     * renders the form, not on a server-rendered page that does not exist here.
     */
    private function configureResetPasswordUrl(): void
    {
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return sprintf(
                '%s/reset-password?token=%s&email=%s',
                rtrim(config('app.url'), '/'),
                $token,
                urlencode($user->getEmailForPasswordReset()),
            );
        });
    }
}
