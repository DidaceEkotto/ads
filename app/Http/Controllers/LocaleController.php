<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Change la langue active (fr / en) en session et en base si connecté.
     */
    public function update(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, ['fr', 'en'], true)) {
            $locale = 'fr';
        }

        $request->session()->put('locale', $locale);

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        return back();
    }
}
