# 13. Production Web Theme Presentation Rules

## Status

Mandatory Foundation Architectural Rule.

## 1. Theme responsibility

A Web theme owns appearance only: design tokens, typography, spacing, color, borders, radius, shadows, focus presentation, responsive presentation, motion, and document presentation. A theme must not own business logic, queries, authorization, routes, controllers, repositories, services, locale selection, or component interaction behavior.

## 2. Dependency and asset isolation

Themes must not depend on Admin or optional business packages. Theme assets must use an output directory isolated from Admin, Installer, and other themes. A theme must not import Admin CSS, JavaScript, Vue code, or Vite entrypoints. Presentation-specific JavaScript is discouraged; stable Web component behavior remains owned by `Webkul\Web`.

## 3. Web component overrides

Theme overrides may change presentation structure only. Every override must preserve the component's public props, slots, semantic elements, IDs, disabled behavior, ARIA attributes, and `data-web-*` interaction hooks. Themes should prefer CSS and tokens and must not copy every Web component.

## 4. Localization and direction

Themes consume `WebContextContract` locale and direction state and must not calculate or select locale independently. Theme CSS must support both RTL and LTR, prefer logical properties, and avoid language-specific component forks.

## 5. Security and progressive enhancement

Themes must preserve Blade escaping and may render raw output only from an explicit trusted rendering contract. Inline behavior handlers are forbidden. Content must remain readable without JavaScript, and motion must respect the user's reduced-motion preference.
