# Website visual assets

`Webkul\\Website` owns deployment-specific visual assets. Put reviewed source files in:

- `src/Resources/assets/branding/` for the primary logo, compact mark, favicon, and default social image;
- `src/Resources/assets/images/` for Website photographs and illustrations;
- `src/Resources/assets/icons/` for small Website-specific SVG icons.

Use SVG for genuine vector logos/icons and WebP or AVIF for suitable raster imagery. Do not inline untrusted SVG, add remote image dependencies by default, or place new files at arbitrary locations under `public/`.

## Publishing and configuration

Website assets use the package's existing publish mechanism:

```bash
php artisan vendor:publish --tag=website-assets --force
```

This copies source assets to `public/vendor/website/`. Configure `website.branding.logo_url`, `website.branding.favicon_url`, and `website.seo.default_image_url` with stable public paths such as `/vendor/website/branding/university-logo.svg`; never use a local filesystem path or a generated Vite hash. Leave a value `null` until a reviewed real asset exists. The Header falls back to text identity, and favicon/SEO image markup is omitted safely.

The base theme's Vite/Tailwind build owns CSS and JavaScript output. From the repository root use `npm install`, `npm run dev`, or `npm run build`. Website assets are not Vite entrypoints, so physically removing this optional package does not break the frontend build.

Locally owned fonts are not currently present. The theme uses a system font stack with `Noto Sans Arabic` and `Noto Sans` as optional installed-font fallbacks.
