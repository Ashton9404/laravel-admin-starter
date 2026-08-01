<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Mark the given user's email address as verified.
     *
     * Unlike Laravel's default flow this route does not require an authenticated
     * session. The verification link is clicked from an email client, which may
     * well open a different browser than the one the user registered in — and a
     * signed URL that already embeds the user id and a hash of the current email
     * address proves ownership on its own.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::find($id);

        if (! $user || ! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect($this->spa('/login?verified=0'));
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($this->spa('/login?verified=already'));
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return redirect($this->spa('/login?verified=1'));
    }

    /**
     * Build an absolute URL back into the SPA.
     */
    private function spa(string $path): string
    {
        return rtrim(config('app.url'), '/').$path;
    }
}
