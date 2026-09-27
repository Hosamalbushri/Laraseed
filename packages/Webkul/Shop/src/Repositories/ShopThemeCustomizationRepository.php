<?php

namespace Webkul\Shop\Repositories;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\Shop\Models\ShopThemeCustomization;

class ShopThemeCustomizationRepository extends Repository
{
    public function model(): string
    {
        return ShopThemeCustomization::class;
    }

    /**
     * Active storefront sections for the given theme code (Bagisto-style query).
     */
    public function getActiveForStorefront(string $themeCode): Collection
    {
        return $this->model
            ->newQuery()
            ->where('theme_code', $themeCode)
            ->where('status', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Active global portal footer for layout (not a home section).
     */
    public function getActivePortalFooter(string $themeCode): ?ShopThemeCustomization
    {
        /** @var ShopThemeCustomization|null $row */
        $row = $this->model
            ->newQuery()
            ->where('theme_code', $themeCode)
            ->where('type', 'portal_footer')
            ->where('status', true)
            ->orderBy('id')
            ->first();

        return $row;
    }

    public function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed)[^>]*>.*?</\1>#is', '', $html) ?? '';

        return preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? $html;
    }

    public function sanitizeCss(string $css): string
    {
        $css = preg_replace('#@import\s+[^;]+;#i', '', $css) ?? '';

        return preg_replace('#expression\s*\(#i', '', $css) ?? $css;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{image: string}>|null  $deletedSliders
     */
    public function mergeCarouselImages(
        ShopThemeCustomization $theme,
        array $rows,
        ?array $deletedSliders
    ): void {
        if (is_array($deletedSliders)) {
            foreach ($deletedSliders as $item) {
                $path = (string) ($item['image'] ?? '');
                if ($path === '') {
                    continue;
                }
                $rel = Str::after($path, 'storage/');
                if ($rel === $path) {
                    $rel = ltrim($path, '/');
                }
                Storage::disk('public')->delete($rel);
            }
        }

        $images = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $link = isset($row['link']) ? (string) $row['link'] : '';
            $title = isset($row['title']) ? (string) $row['title'] : '';
            $imageVal = $row['image'] ?? '';

            if ($imageVal instanceof UploadedFile) {
                $stored = $imageVal->store("shop-theme/{$theme->id}", 'public');
                $images[] = [
                    'image' => $stored,
                    'link' => $link,
                    'title' => $title,
                ];
            } elseif (is_string($imageVal) && $imageVal !== '') {
                $images[] = [
                    'image' => str_replace('storage/', '', ltrim($imageVal, '/')),
                    'link' => $link,
                    'title' => $title,
                ];
            }
        }

        $opts = $theme->options ?? [];
        $opts['images'] = $images;
        $theme->options = $opts;
        $theme->save();
    }

    public function uploadStaticContentImage(ShopThemeCustomization $theme, UploadedFile $file): string
    {
        $path = $file->store("shop-theme/{$theme->id}/editor", 'public');

        return Storage::url($path);
    }
}
