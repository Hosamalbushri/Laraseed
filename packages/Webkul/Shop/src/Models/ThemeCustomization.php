<?php

namespace Webkul\Shop\Models;

/**
 * Type constants aligned with Bagisto's theme customizations (see bagisto ThemeCustomization model).
 * Home sections are driven from config('shop.home_customizations').
 */
class ThemeCustomization
{
    public const IMAGE_CAROUSEL = 'image_carousel';

    public const PRODUCT_CAROUSEL = 'product_carousel';

    public const CATEGORY_CAROUSEL = 'category_carousel';

    public const STATIC_CONTENT = 'static_content';

    public const FOOTER_LINKS = 'footer_links';

    public const SERVICES_CONTENT = 'services_content';

    /**
     * Portal-specific: horizontal strip of published events (replaces product carousel data source).
     */
    public const EVENT_CAROUSEL = 'event_carousel';

    /**
     * Full-viewport hero (dark gradient, optional particles/orbs, typing line, CTAs, stats, stacked cards).
     * Options are fully editable from the admin theme customization screen.
     */
    public const IMMERSIVE_HERO = 'immersive_hero';

    /**
     * Global storefront footer (all pages). One active row per theme_code recommended.
     * Not rendered as a home section — loaded from the main layout footer.
     */
    public const PORTAL_FOOTER = 'portal_footer';
}
