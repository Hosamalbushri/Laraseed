<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Webkul\Event\Repositories\EventCategoryRepository;
use Webkul\Event\Repositories\EventRepository;
use Webkul\Shop\Models\ShopThemeCustomization;
use Webkul\Shop\Models\ThemeCustomization;
use Webkul\Shop\Repositories\ShopThemeCustomizationRepository;

class HomeController extends Controller
{
    use Concerns\ResolvesStudentSubscribedEventIds;

    public function __construct(
        protected EventRepository $eventRepository,
        protected EventCategoryRepository $eventCategoryRepository,
        protected ShopThemeCustomizationRepository $shopThemeCustomizationRepository
    ) {}

    public function index(): View
    {
        $customizations = $this->resolvedCustomizations();

        return view('shop::home.index', [
            'customizations' => $customizations,
            'homeSeo' => config('shop.home_seo', []),
            'subscribedEventIds' => $this->studentSubscribedEventIds(),
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    protected function resolvedCustomizations(): Collection
    {
        if (
            Schema::hasTable('shop_theme_customizations')
            && Schema::hasColumn('shop_theme_customizations', 'options')
            && $this->shopThemeCustomizationRepository->getModel()
                ->newQuery()
                ->where('theme_code', config('shop.storefront_theme_code', 'default'))
                ->where('status', true)
                ->exists()
        ) {
            return $this->resolvedFromDatabase();
        }

        return $this->resolvedFromConfig();
    }

    /**
     * @return Collection<int, object>
     */
    protected function resolvedFromDatabase(): Collection
    {
        $themeCode = config('shop.storefront_theme_code', 'default');

        return $this->shopThemeCustomizationRepository
            ->getActiveForStorefront($themeCode)
            ->filter(fn (ShopThemeCustomization $row) => $row->type !== ThemeCustomization::PORTAL_FOOTER)
            ->values()
            ->map(function (ShopThemeCustomization $row) {
                $options = is_array($row->options) ? $row->options : [];
                $obj = (object) [
                    'type' => $row->type,
                    'options' => $options,
                ];

                if ($row->type === ThemeCustomization::EVENT_CAROUSEL) {
                    $limit = max(1, min(24, (int) ($options['limit'] ?? 8)));
                    $obj->_events = $this->eventRepository->getModel()
                        ->newQuery()
                        ->published()
                        ->with('categories')
                        ->withCount('subscribers')
                        ->orderByDesc('id')
                        ->limit($limit)
                        ->get();
                }

                if ($row->type === ThemeCustomization::CATEGORY_CAROUSEL) {
                    $limit = max(1, min(48, (int) ($options['limit'] ?? 24)));
                    $obj->_categories = $this->eventCategoryRepository->getModel()
                        ->newQuery()
                        ->where('status', true)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->limit($limit)
                        ->get();
                }

                return $obj;
            });
    }

    /**
     * @return Collection<int, object>
     */
    protected function resolvedFromConfig(): Collection
    {
        $rows = collect(config('shop.home_customizations', []))
            ->filter(fn (array $row) => (int) ($row['status'] ?? 1) === 1)
            ->filter(fn (array $row) => ($row['type'] ?? '') !== ThemeCustomization::PORTAL_FOOTER)
            ->sortBy(fn (array $row, int $i) => $row['sort_order'] ?? $i)
            ->values();

        return $rows->map(function (array $row) {
            $type = (string) ($row['type'] ?? '');
            $options = is_array($row['options'] ?? null) ? $row['options'] : [];

            if ($type === ThemeCustomization::EVENT_CAROUSEL) {
                $limit = max(1, min(24, (int) ($options['limit'] ?? 8)));
                $row['_events'] = $this->eventRepository->getModel()
                    ->newQuery()
                    ->published()
                    ->with('categories')
                    ->withCount('subscribers')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get();
            }

            if ($type === ThemeCustomization::CATEGORY_CAROUSEL) {
                $limit = max(1, min(48, (int) ($options['limit'] ?? 24)));
                $row['_categories'] = $this->eventCategoryRepository->getModel()
                    ->newQuery()
                    ->where('status', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->limit($limit)
                    ->get();
            }

            $row['options'] = $options;

            return (object) $row;
        });
    }
}
