<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Persist the signed-in user's language choice.
     *
     * Guests keep their preference in localStorage only; storing it server-side
     * is what makes the choice follow the account to another browser.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(Locales::codes())],
        ]);

        $request->user()->forceFill($validated)->save();

        app()->setLocale($validated['locale']);

        return response()->json(['locale' => $validated['locale']]);
    }
}
