<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch application locale.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, SetLocaleMiddleware::SUPPORTED_LOCALES, true)) {
            abort(400, 'Bahasa yang dipilih tidak didukung / Unsupported locale.');
        }

        $request->session()->put('locale', $locale);

        return redirect()->back();
    }
}
