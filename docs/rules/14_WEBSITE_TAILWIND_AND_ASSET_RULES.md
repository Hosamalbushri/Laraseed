# Website Tailwind and Asset Rules

These rules govern public presentation owned by the optional `Webkul\\Website` package.

1. Website Blade markup uses Tailwind utilities first. Do not add a second CSS/UI framework or recreate utility styling in Website component CSS.
2. Repeated Website design concepts use the base theme's small Tailwind token extension. Arbitrary values are reserved for genuine exceptions.
3. Tailwind content discovery may scan optional Website views, but Website files must never be unconditional Vite entrypoints. The frontend build must continue to pass after physical Website removal.
4. `SiteDefinition` remains the only Website identity, contact, logo, favicon, and default SEO image authority. Never hardcode generated build hashes in PHP, Blade, or configuration.
5. Website-specific logos, favicons, imagery, and icons belong under `packages/Webkul/Website/src/Resources/assets/`. Generic theme visuals belong to Theme. Generic runtime assets belong to Web. Do not dump assets into arbitrary `public/` paths.
6. Source assets are editable package inputs; `public/themes/*/build/` is generated, disposable output. Published Website visual assets use the package's documented `website-assets` mechanism.
7. Use reviewed SVG files as image assets; never expose configurable raw SVG/HTML. Prefer WebP/AVIF for suitable raster images, constrain image aspect/size, and lazy-load non-critical images.
8. Website navigation comes from `NavigationRegistry`, homepage composition comes from `SectionRegistry`, and shell identity comes from `SiteDefinition`.
9. Header, footer, locale controls, mobile navigation, focus visibility, active state, and LTR/RTL behavior must remain accessible and responsive without adding a JavaScript framework.
10. Foundation packages must not import Website production classes or require Website assets. Website, Student, and LostAndFound dependency boundaries remain unchanged.

Operational asset details live in `packages/Webkul/Website/docs/ASSETS.md`.
