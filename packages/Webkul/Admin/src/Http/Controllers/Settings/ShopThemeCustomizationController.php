<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Settings\ShopThemeCustomizationDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Shop\Models\ShopThemeCustomization;
use Webkul\Shop\Models\ThemeCustomization;
use Webkul\Shop\Repositories\ShopThemeCustomizationRepository;

class ShopThemeCustomizationController extends Controller
{
    /**
     * Types allowed when creating a new section (product carousel excluded for this portal).
     *
     * @var list<string>
     */
    private const TYPES_CREATABLE = [
        'image_carousel',
        'static_content',
        'event_carousel',
        'category_carousel',
        'footer_links',
        'services_content',
        'immersive_hero',
        'portal_footer',
    ];

    /**
     * Types allowed on update (legacy rows may still be product_carousel).
     *
     * @return list<string>
     */
    private static function typesForUpdate(): array
    {
        return array_merge(self::TYPES_CREATABLE, ['product_carousel']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeImmersiveHeroOptions(Request $request): array
    {
        $o = $request->input('options', []);
        $o = is_array($o) ? $o : [];

        $wordsText = (string) data_get($o, 'typing.words_text', '');
        $words = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $wordsText) ?: [])));

        $cards = [];
        foreach ([0, 1, 2] as $i) {
            $cards[] = [
                'icon' => $this->sanitizeImmersiveFaClass((string) data_get($o, "cards.$i.icon", '')),
                'date_line' => mb_substr(trim((string) data_get($o, "cards.$i.date_line", '')), 0, 191),
                'title' => mb_substr(trim((string) data_get($o, "cards.$i.title", '')), 0, 191),
                'attendees' => mb_substr(trim((string) data_get($o, "cards.$i.attendees", '')), 0, 191),
            ];
        }

        return [
            'effects' => [
                'particles' => $request->boolean('options.effects.particles'),
                'orbs' => $request->boolean('options.effects.orbs'),
                'grid' => $request->boolean('options.effects.grid'),
                'custom_cursor' => $request->boolean('options.effects.custom_cursor'),
                'visual_cards' => $request->boolean('options.effects.visual_cards'),
                'scroll_hint' => $request->boolean('options.effects.scroll_hint'),
                'font_awesome' => $request->boolean('options.effects.font_awesome'),
            ],
            'particles_count' => max(20, min(200, (int) data_get($o, 'particles_count', 80))),
            'colors' => [
                'bg_start' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.bg_start', '#0a0a2a'), '#0a0a2a', true),
                'bg_mid' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.bg_mid', '#1a1a3a'), '#1a1a3a', true),
                'bg_end' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.bg_end', '#0f0f2a'), '#0f0f2a', true),
                'accent' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.accent', '#8b5cf6'), '#8b5cf6', true),
                'accent_2' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.accent_2', '#6366f1'), '#6366f1', true),
                'text' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.text', '#ffffff'), '#ffffff', false),
                'text_muted' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.text_muted', 'rgba(255,255,255,0.7)'), 'rgba(255,255,255,0.7)', false),
                'orb_1' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.orb_1', 'rgba(139, 92, 246, 0.8)'), 'rgba(139, 92, 246, 0.8)', false),
                'orb_2' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.orb_2', 'rgba(236, 72, 153, 0.6)'), 'rgba(236, 72, 153, 0.6)', false),
                'orb_3' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.orb_3', 'rgba(59, 130, 246, 0.7)'), 'rgba(59, 130, 246, 0.7)', false),
            ],
            'badge' => [
                'enabled' => $request->boolean('options.badge.enabled'),
                'icon' => $this->sanitizeImmersiveFaClass((string) data_get($o, 'badge.icon', 'fas fa-calendar-star')),
                'text' => mb_substr(trim((string) data_get($o, 'badge.text', '')), 0, 255),
            ],
            'heading' => [
                'line1' => mb_substr(trim((string) data_get($o, 'heading.line1', '')), 0, 500),
                'highlight' => mb_substr(trim((string) data_get($o, 'heading.highlight', '')), 0, 255),
            ],
            'typing' => [
                'prefix' => mb_substr(trim((string) data_get($o, 'typing.prefix', '')), 0, 191),
                'words' => array_slice($words, 0, 30),
            ],
            'description' => mb_substr(trim((string) data_get($o, 'description', '')), 0, 2000),
            'primary_cta' => [
                'label' => mb_substr(trim((string) data_get($o, 'primary_cta.label', '')), 0, 191),
                'url' => $this->sanitizeImmersiveUrl((string) data_get($o, 'primary_cta.url', '')),
                'icon' => $this->sanitizeImmersiveFaClass((string) data_get($o, 'primary_cta.icon', 'fas fa-compass')),
            ],
            'secondary_cta' => [
                'enabled' => $request->boolean('options.secondary_cta.enabled'),
                'label' => mb_substr(trim((string) data_get($o, 'secondary_cta.label', '')), 0, 191),
                'url' => $this->sanitizeImmersiveUrl((string) data_get($o, 'secondary_cta.url', '')),
                'icon' => $this->sanitizeImmersiveFaClass((string) data_get($o, 'secondary_cta.icon', 'fas fa-plus-circle')),
            ],
            'cards' => $cards,
            'scroll_hint' => [
                'text' => mb_substr(trim((string) data_get($o, 'scroll_hint.text', '')), 0, 191),
            ],
        ];
    }

    protected function sanitizeImmersiveColor(string $value, string $fallback, bool $hexOnly): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        if (strlen($value) > 120) {
            return $fallback;
        }
        if ($hexOnly) {
            return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : $fallback;
        }
        if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value)) {
            return $value;
        }
        if (preg_match('/^rgba?\([^)]+\)$/i', $value)) {
            return $value;
        }

        return $fallback;
    }

    protected function sanitizeImmersiveUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (strlen($url) > 2048) {
            return '';
        }
        if (str_starts_with($url, '/') || str_starts_with($url, '#') || str_starts_with($url, '?')) {
            return $url;
        }
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return '';
    }

    protected function sanitizeImmersiveFaClass(string $class): string
    {
        $class = trim(preg_replace('/\s+/', ' ', $class) ?? '');
        if ($class === '') {
            return '';
        }
        if (strlen($class) > 80) {
            return '';
        }
        if (! preg_match('/^[a-z0-9\s\-]+$/i', $class)) {
            return '';
        }

        return $class;
    }

    public function __construct(
        protected ShopThemeCustomizationRepository $shopThemeCustomizationRepository
    ) {}

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(ShopThemeCustomizationDataGrid::class)->process();
        }

        return view('admin::settings.shop-theme.index');
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->filled('id') && $request->hasFile('image')) {
            return $this->storeStaticEditorImage($request);
        }

        $this->validate($request, [
            'name' => 'required|string|max:191',
            'sort_order' => 'required|integer|min:0',
            'type' => 'required|in:'.implode(',', self::TYPES_CREATABLE),
            'theme_code' => 'required|string|max:64',
        ]);

        if ($request->input('type') === ThemeCustomization::PORTAL_FOOTER) {
            $exists = ShopThemeCustomization::query()
                ->where('theme_code', $request->input('theme_code'))
                ->where('type', ThemeCustomization::PORTAL_FOOTER)
                ->exists();
            if ($exists) {
                return new JsonResponse([
                    'message' => trans('admin::app.settings.shop-theme.create.portal-footer-duplicate'),
                    'errors' => ['type' => [trans('admin::app.settings.shop-theme.create.portal-footer-duplicate')]],
                ], 422);
            }
        }

        $theme = $this->shopThemeCustomizationRepository->create([
            'name' => $request->input('name'),
            'sort_order' => (int) $request->input('sort_order'),
            'type' => $request->input('type'),
            'theme_code' => $request->input('theme_code'),
            'status' => false,
            'options' => [],
        ]);

        return new JsonResponse([
            'redirect_url' => route('admin.settings.shop-theme.edit', $theme->id),
        ]);
    }

    public function edit(int $id): View
    {
        $theme = $this->shopThemeCustomizationRepository->findOrFail($id);

        return view('admin::settings.shop-theme.edit', compact('theme'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required|string|max:191',
            'sort_order' => 'required|integer|min:0',
            'type' => 'required|in:'.implode(',', self::typesForUpdate()),
            'theme_code' => 'required|string|max:64',
        ]);

        /** @var ShopThemeCustomization $theme */
        $theme = $this->shopThemeCustomizationRepository->findOrFail($id);

        if ($request->input('type') === ThemeCustomization::PORTAL_FOOTER) {
            $dup = ShopThemeCustomization::query()
                ->where('theme_code', $request->input('theme_code'))
                ->where('type', ThemeCustomization::PORTAL_FOOTER)
                ->where('id', '!=', $id)
                ->exists();
            if ($dup) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['type' => trans('admin::app.settings.shop-theme.create.portal-footer-duplicate')]);
            }
        }

        $theme->update([
            'name' => $request->input('name'),
            'sort_order' => (int) $request->input('sort_order'),
            'type' => $request->input('type'),
            'theme_code' => $request->input('theme_code'),
            'status' => $request->boolean('status'),
        ]);

        $this->syncOptions($request, $theme);

        session()->flash('success', trans('admin::app.settings.shop-theme.update-success'));

        return redirect()->route('admin.settings.shop-theme.index');
    }

    public function destroy(int $id): JsonResponse
    {
        $theme = $this->shopThemeCustomizationRepository->findOrFail($id);

        Storage::disk('public')->deleteDirectory('shop-theme/'.$theme->id);

        $this->shopThemeCustomizationRepository->delete($id);

        return new JsonResponse([
            'message' => trans('admin::app.settings.shop-theme.delete-success'),
        ], 200);
    }

    protected function storeStaticEditorImage(Request $request): JsonResponse
    {
        $this->validate($request, [
            'id' => 'required|integer|exists:shop_theme_customizations,id',
            'image' => 'required|image|max:5120',
        ]);

        $theme = $this->shopThemeCustomizationRepository->findOrFail((int) $request->input('id'));

        if ($theme->type !== 'static_content') {
            return response()->json(['message' => 'Invalid type'], 422);
        }

        $url = $this->shopThemeCustomizationRepository->uploadStaticContentImage(
            $theme,
            $request->file('image')
        );

        return response()->json($url);
    }

    protected function syncOptions(Request $request, ShopThemeCustomization $theme): void
    {
        $repo = $this->shopThemeCustomizationRepository;

        switch ($theme->type) {
            case 'static_content':
                $html = $repo->sanitizeHtml((string) $request->input('options.html', ''));
                $css = $repo->sanitizeCss((string) $request->input('options.css', ''));
                $theme->options = ['html' => $html, 'css' => $css];
                $theme->save();

                break;

            case 'event_carousel':
                $this->validate($request, [
                    'options.title' => 'nullable|string|max:255',
                    'options.limit' => 'nullable|integer|min:1|max:24',
                ]);
                $theme->options = [
                    'title' => (string) $request->input('options.title', ''),
                    'limit' => max(1, min(24, (int) $request->input('options.limit', 8))),
                ];
                $theme->save();

                break;

            case 'category_carousel':
                $this->validate($request, [
                    'options.title' => 'nullable|string|max:255',
                    'options.limit' => 'nullable|integer|min:1|max:48',
                ]);
                $theme->options = [
                    'title' => (string) $request->input('options.title', ''),
                    'limit' => max(1, min(48, (int) $request->input('options.limit', 24))),
                ];
                $theme->save();

                break;

            case 'footer_links':
                $theme->options = [
                    'sections' => $this->normalizeFooterSections($request->input('footer.sections', [])),
                ];
                $theme->save();

                break;

            case 'services_content':
                $theme->options = [
                    'services' => $this->normalizeServices($request->input('services', [])),
                ];
                $theme->save();

                break;

            case 'product_carousel':
                $theme->options = [];
                $theme->save();

                break;

            case 'immersive_hero':
                $theme->options = $this->normalizeImmersiveHeroOptions($request);
                $theme->save();

                break;

            case 'portal_footer':
                $theme->options = $this->normalizePortalFooterOptions($request, $theme);
                $theme->save();

                break;

            case 'image_carousel':
                $raw = $request->input('options', []);
                $merged = [];
                if (is_array($raw)) {
                    ksort($raw);
                    foreach ($raw as $i => $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $file = $request->file('options.'.$i.'.image');
                        $path = (string) ($row['image_path'] ?? '');
                        $image = $file instanceof UploadedFile ? $file : $path;
                        if ($image === '') {
                            continue;
                        }
                        $merged[] = [
                            'image' => $image,
                            'link' => (string) ($row['link'] ?? ''),
                            'title' => (string) ($row['title'] ?? ''),
                        ];
                    }
                }
                $deleted = $request->input('deleted_sliders');
                $deleted = is_array($deleted) ? $deleted : null;
                $repo->mergeCarouselImages($theme, $merged, $deleted);

                break;

            default:
                $theme->options = [];
                $theme->save();
        }
    }

    /**
     * @param  mixed  $sections
     * @return list<array{links: list<array{title: string, url: string, sort_order: int}>}>
     */
    protected function normalizeFooterSections($sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        $out = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $linksRaw = $section['links'] ?? [];
            if (! is_array($linksRaw)) {
                continue;
            }

            $links = [];
            $order = 0;

            foreach ($linksRaw as $link) {
                if (! is_array($link)) {
                    continue;
                }

                $title = trim((string) ($link['title'] ?? ''));
                $url = trim((string) ($link['url'] ?? ''));

                if ($title === '' && $url === '') {
                    continue;
                }

                $links[] = [
                    'title' => $title,
                    'url' => $url,
                    'sort_order' => (int) ($link['sort_order'] ?? $order),
                ];
                $order++;
            }

            usort($links, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

            if ($links !== []) {
                $out[] = ['links' => $links];
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $raw
     * @return list<array{service_icon: string, title: string, description: string}>
     */
    protected function normalizeServices($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));
            $icon = trim((string) ($row['service_icon'] ?? 'icon-calendar'));

            if ($title === '' && $description === '') {
                continue;
            }

            if (! preg_match('/^[a-z0-9\-]+$/i', $icon)) {
                $icon = 'icon-calendar';
            }

            $out[] = [
                'service_icon' => $icon,
                'title' => $title,
                'description' => $description,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizePortalFooterOptions(Request $request, ShopThemeCustomization $theme): array
    {
        $o = $request->input('options', []);
        $o = is_array($o) ? $o : [];

        $social = [];
        foreach (range(0, 7) as $i) {
            $icon = $this->sanitizeImmersiveFaClass((string) data_get($o, "social.$i.icon", ''));
            $url = $this->sanitizeImmersiveUrl((string) data_get($o, "social.$i.url", ''));
            if ($icon === '' && $url === '') {
                continue;
            }
            $social[] = ['icon' => $icon, 'url' => $url];
        }

        $colQuick = $this->normalizePortalFooterColumn(
            $o,
            'col_quick',
            $request->boolean('options.col_quick.show_chevron')
        );

        $brandLogoPath = $this->normalizePortalFooterBrandLogo($request, $theme, $o);

        $contactItems = [];
        foreach (range(0, 5) as $i) {
            $icon = $this->sanitizeImmersiveFaClass((string) data_get($o, "contact.items.$i.icon", ''));
            $text = mb_substr(trim((string) data_get($o, "contact.items.$i.text", '')), 0, 500);
            if ($icon === '' && $text === '') {
                continue;
            }
            $contactItems[] = ['icon' => $icon, 'text' => $text];
        }

        $bottomLinks = [];
        foreach (range(0, 9) as $i) {
            $label = mb_substr(trim((string) data_get($o, "bottom.links.$i.label", '')), 0, 191);
            $url = $this->sanitizeImmersiveUrl((string) data_get($o, "bottom.links.$i.url", ''));
            if ($label === '' && $url === '') {
                continue;
            }
            $bottomLinks[] = ['label' => $label, 'url' => $url];
        }

        $method = strtolower((string) data_get($o, 'newsletter.form_method', 'post'));
        if (! in_array($method, ['get', 'post'], true)) {
            $method = 'post';
        }

        return [
            'enabled' => $request->boolean('options.enabled'),
            'effects' => [
                'orbs' => $request->boolean('options.effects.orbs'),
                'grid' => $request->boolean('options.effects.grid'),
                'parallax' => $request->boolean('options.effects.parallax'),
                'font_awesome' => $request->boolean('options.effects.font_awesome'),
                'back_to_top' => $request->boolean('options.effects.back_to_top'),
            ],
            'colors' => [
                'bg_start' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.bg_start', '#0a0a2a'), '#0a0a2a', true),
                'bg_end' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.bg_end', '#030318'), '#030318', true),
                'accent' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.accent', '#8b5cf6'), '#8b5cf6', true),
                'accent_2' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.accent_2', '#6366f1'), '#6366f1', true),
                'border_top' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.border_top', 'rgba(139, 92, 246, 0.2)'), 'rgba(139, 92, 246, 0.2)', false),
                'text' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.text', '#ffffff'), '#ffffff', false),
                'text_muted' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.text_muted', 'rgba(255,255,255,0.6)'), 'rgba(255,255,255,0.6)', false),
                'orb_1' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.orb_1', 'rgba(139, 92, 246, 0.6)'), 'rgba(139, 92, 246, 0.6)', false),
                'orb_2' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.orb_2', 'rgba(236, 72, 153, 0.5)'), 'rgba(236, 72, 153, 0.5)', false),
                'orb_3' => $this->sanitizeImmersiveColor((string) data_get($o, 'colors.orb_3', 'rgba(59, 130, 246, 0.4)'), 'rgba(59, 130, 246, 0.4)', false),
            ],
            'brand' => [
                'logo_path' => $brandLogoPath,
                'logo_icon' => $this->sanitizeImmersiveFaClass((string) data_get($o, 'brand.logo_icon', 'fas fa-graduation-cap')),
                'title' => mb_substr(trim((string) data_get($o, 'brand.title', '')), 0, 191),
                'description' => mb_substr(trim((string) data_get($o, 'brand.description', '')), 0, 2000),
            ],
            'social' => $social,
            'col_quick' => $colQuick,
            'contact' => [
                'title' => mb_substr(trim((string) data_get($o, 'contact.title', '')), 0, 191),
                'items' => $contactItems,
            ],
            'newsletter' => [
                'enabled' => $request->boolean('options.newsletter.enabled'),
                'title' => mb_substr(trim((string) data_get($o, 'newsletter.title', '')), 0, 191),
                'text' => mb_substr(trim((string) data_get($o, 'newsletter.text', '')), 0, 1000),
                'placeholder' => mb_substr(trim((string) data_get($o, 'newsletter.placeholder', '')), 0, 191),
                'button_label' => mb_substr(trim((string) data_get($o, 'newsletter.button_label', '')), 0, 191),
                'form_action' => $this->sanitizeImmersiveUrl((string) data_get($o, 'newsletter.form_action', '')),
                'form_method' => $method,
            ],
            'bottom' => [
                'copyright' => mb_substr(trim((string) data_get($o, 'bottom.copyright', '')), 0, 2000),
                'links' => $bottomLinks,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $o
     * @return array{title: string, show_chevron: bool, links: list<array{label: string, url: string}>}
     */
    protected function normalizePortalFooterColumn(array $o, string $key, bool $showChevron): array
    {
        $links = [];
        foreach (range(0, 11) as $i) {
            $label = mb_substr(trim((string) data_get($o, "$key.links.$i.label", '')), 0, 191);
            $url = $this->sanitizeImmersiveUrl((string) data_get($o, "$key.links.$i.url", ''));
            if ($label === '' && $url === '') {
                continue;
            }
            $links[] = ['label' => $label, 'url' => $url];
        }

        return [
            'title' => mb_substr(trim((string) data_get($o, "$key.title", '')), 0, 191),
            'show_chevron' => $showChevron,
            'links' => $links,
        ];
    }

    /**
     * Store or keep portal footer brand logo; paths are limited to this theme's folder on the public disk.
     */
    protected function normalizePortalFooterBrandLogo(Request $request, ShopThemeCustomization $theme, array $o): string
    {
        $prev = trim((string) data_get($theme->options, 'brand.logo_path', ''));

        if ($request->boolean('options.brand.remove_logo')) {
            if ($prev !== '' && Storage::disk('public')->exists($prev)) {
                Storage::disk('public')->delete($prev);
            }

            return '';
        }

        $file = $request->file('options.brand.logo_image');
        if ($file instanceof UploadedFile && $file->isValid()) {
            $mime = (string) $file->getMimeType();
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            if (! in_array($mime, $allowed, true)) {
                return $this->assertPortalFooterLogoPathOwnedByTheme($prev, $theme);
            }

            if ($prev !== '' && Storage::disk('public')->exists($prev)) {
                Storage::disk('public')->delete($prev);
            }

            return $file->store('shop-theme/'.$theme->id.'/footer', 'public');
        }

        $fromForm = trim((string) data_get($o, 'brand.logo_path', ''));

        return $this->assertPortalFooterLogoPathOwnedByTheme($fromForm !== '' ? $fromForm : $prev, $theme);
    }

    protected function assertPortalFooterLogoPathOwnedByTheme(string $path, ShopThemeCustomization $theme): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        $prefix = 'shop-theme/'.$theme->id.'/';
        if (! str_starts_with($path, $prefix) || str_contains($path, '..')) {
            return '';
        }

        if (! Storage::disk('public')->exists($path)) {
            return '';
        }

        return $path;
    }
}
