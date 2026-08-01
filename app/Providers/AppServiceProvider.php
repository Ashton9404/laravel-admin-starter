<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePasswordPolicy();
        $this->configureResetPasswordUrl();
        $this->configureAuthorization();
    }

    /**
     * Administrators bypass every gate and policy.
     *
     * Returning null (rather than false) for everyone else is deliberate: it
     * means "no opinion", so the normal policy still gets to decide.
     */
    private function configureAuthorization(): void
    {
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
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
