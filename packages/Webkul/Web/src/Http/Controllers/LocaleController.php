<?php

namespace Webkul\Web\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Core\Repositories\LocaleRepository;

class LocaleController extends Controller
{
    public function __construct(
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Switch current web content locale.
     */
    public function switch(Request $request, string $code): RedirectResponse
    {
        $isValid = false;

        try {
            $isValid = $this->localeRepository->isActiveCode($code);
        } catch (\Throwable) {
            $isValid = false;
        }

        if (! $isValid) {
            return redirect()->back()->with('error', trans('web::app.locale.invalid'));
        }

        if ($request->hasSession()) {
            $request->session()->put('web_locale', $code);
        }

        return redirect()->back()->withCookie(cookie()->forever('web_locale', $code));
    }
}
