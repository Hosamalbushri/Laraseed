# 13. Production Web Theme Presentation and Asset Build Rules

## Status

Mandatory Foundation Architectural Rule.

## 1. Theme responsibility

A Web theme owns appearance only: design tokens, typography, spacing, color, borders, radius, shadows, focus presentation, responsive presentation, motion, and document presentation. A theme must not own business logic, queries, authorization, routes, controllers, repositories, services, locale selection, or component interaction behavior.

## 2. Dependency and asset isolation

1. **Source vs Build Separation**:
   - `Webkul\Web` owns the generic JavaScript interaction source (`packages/Webkul/Web/src/Resources/assets/js/`).
   - The concrete theme owns its visual CSS source and design tokens (`themes/{theme}/assets/css/theme.css`).
   - The concrete theme's build configuration compiles its CSS along with the generic Web JS runtime via Vite.
   - Compiled assets are generated into `public/themes/{theme}/build/`.
2. **Build Isolation**:
   - Themes must not depend on Admin or optional business packages.
   - Theme assets must use an output directory isolated from Admin, Installer, and other themes (`public/themes/{theme}/build/`).
   - A theme must not import Admin CSS, JavaScript, Vue code, or Vite entrypoints.
   - Presentation-specific JavaScript is discouraged; stable Web component behavior remains owned by `Webkul\Web`.

## 3. Theme Build vs Runtime Selection

1. **Runtime Theme Resolution** and **Frontend Theme Compilation** are distinct lifecycle phases.
2. A theme is certifiably **Theme Build Ready** when it has a valid `theme.json`, its CSS compiles via Vite without errors, and its manifest contains both its CSS and Web JS runtime entries.
3. A theme is **Theme Runtime Ready** when its compiled bundle exists in `public/themes/{theme}/build/manifest.json`. Changing `APP_THEME` requires the target theme to be built beforehand.

## 4. Web component overrides

Theme overrides may change presentation structure only. Every override must preserve the component's public props, slots, semantic elements, IDs, disabled behavior, ARIA attributes, and `data-web-*` interaction hooks. Themes should prefer CSS and tokens and must not copy every Web component.

## 5. Localization and direction

Themes consume `WebContextContract` locale and direction state and must not calculate or select locale independently. Theme CSS must support both RTL and LTR, prefer logical properties, and avoid language-specific component forks.

## 6. Security and progressive enhancement

Themes must preserve Blade escaping and may render raw output only from an explicit trusted rendering contract. Inline behavior handlers are forbidden. Content must remain readable without JavaScript, and motion must respect the user's reduced-motion preference.
