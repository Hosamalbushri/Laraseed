<?php

namespace Webkul\Web\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Services\ContentLocaleService;
use Webkul\Theme\Contracts\ThemeResolverContract;
use Webkul\Theme\View\ThemeViewFinder;
use Webkul\Web\Context\WebContext;
use Webkul\Web\Contracts\WebContextContract;

class ResolveWebLocale
{
    public function __construct(
        protected ContentLocaleService $contentLocaleService,
        protected LocaleRepository $localeRepository,
        protected ThemeResolverContract $themeResolver,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestedLocale = $this->determineRequestedLocale($request);

        $isValid = false;
        if ($requestedLocale) {
            try {
                $isValid = $this->localeRepository->isActiveCode($requestedLocale);
            } catch (\Throwable) {
                $isValid = false;
            }
        }

        if ($isValid) {
            $effectiveLocale = $requestedLocale;
        } else {
            $effectiveLocale = $this->contentLocaleService->primaryContentLocale()->code;
        }

        app()->setLocale($effectiveLocale);

        $directionEnum = $this->localeRepository->directionFor($effectiveLocale);
        $direction = $directionEnum ? $directionEnum->value : 'ltr';

        $activeTheme = $this->themeResolver->resolveActiveTheme();
        $viewFinder = view()->getFinder();

        if ($viewFinder instanceof ThemeViewFinder) {
            $viewFinder->setActiveThemeChain($this->themeResolver->resolveActiveInheritanceChain());
        }

        $webContext = new WebContext(
            locale: $effectiveLocale,
            direction: $direction,
            activeTheme: $activeTheme->id,
            canonicalUrl: $request->url(),
        );

        app()->instance(WebContextContract::class, $webContext);
        app()->instance(WebContext::class, $webContext);

        if (function_exists('view')) {
            view()->share('webContext', $webContext);
        }

        return $next($request);
    }

    /**
     * Determine requested locale code from session, cookie, or query parameter.
     */
    protected function determineRequestedLocale(Request $request): ?string
    {
        if ($request->hasSession() && $request->session()->has('web_locale')) {
            return (string) $request->session()->get('web_locale');
        }

        if ($request->cookies->has('web_locale')) {
            return (string) $request->cookies->get('web_locale');
        }

        return null;
    }
}
