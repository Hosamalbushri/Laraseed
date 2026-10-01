# Laraseed Architectural Rules Index

This directory contains authoritative architectural rules governing development in Laraseed.

- Repository instructions and task-specific approved architecture remain the highest authority.
- [`06_PACKAGE_AND_LOCALIZATION_RULES.md`](./06_PACKAGE_AND_LOCALIZATION_RULES.md): Repository-wide package ownership and two-layer localization rules, model classification gates, and future Locale/content design constraints.
- [`07_ADMIN_UI_PAGE_RULES.md`](./07_ADMIN_UI_PAGE_RULES.md): Admin presentation rules for both Foundation pages and package-owned pages rendered with Admin infrastructure.
- [`08_SEED_FOUNDATION_AND_PACKAGE_ISOLATION_RULES.md`](./08_SEED_FOUNDATION_AND_PACKAGE_ISOLATION_RULES.md): Foundation boundary, dependency direction, optional-package isolation, composition metadata, and removability rules.
- [`09_PACKAGE_INTERNAL_ARCHITECTURE_AND_EXTENSION_RULES.md`](./09_PACKAGE_INTERNAL_ARCHITECTURE_AND_EXTENSION_RULES.md): Permanent package-internal architecture, public integration surfaces, extension mechanisms, dependency manifests, and completion gates.
- [`11_PERSISTENCE_AND_NO_UNDO_RULES.md`](./11_PERSISTENCE_AND_NO_UNDO_RULES.md): Architecture persistence invariants, strict forward progress, and no-undo rules.
- [`../architecture/FOUNDATION_ARCHITECTURE.md`](../architecture/FOUNDATION_ARCHITECTURE.md): Current Foundation classification, dependency graph, deployment composition, development baseline, and optional-package architecture.

All agents and developers modifying package or Admin behavior MUST read the applicable repository instructions and all rules above before implementation. Conceptual authority flows from general repository rules to package/localization rules, seed isolation rules, package-internal architecture rules, Admin UI rules, and finally feature implementation. Numeric filenames identify documents; they do not override that conceptual precedence.
