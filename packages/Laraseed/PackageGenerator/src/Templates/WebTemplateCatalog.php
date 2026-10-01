<?php

namespace Laraseed\PackageGenerator\Templates;

use Laraseed\PackageGenerator\Exceptions\PackageGenerationException;

class WebTemplateCatalog
{
    /**
     * Default template ID.
     */
    public const DEFAULT_TEMPLATE = 'starter';

    /**
     * Registered Web templates metadata.
     *
     * @var array<string, array{id: string, name: string, description: string, files: array<string, string>}>
     */
    protected static array $templates = [
        'starter' => [
            'id' => 'starter',
            'name' => 'Web Starter',
            'description' => 'Standard Web Starter template with Blade components, Tailwind CSS, Cairo typography, Vue 3, and package-isolated routes.',
            'files' => [
                'package.json'                                                      => 'templates/starter/package.json.stub',
                'vite.config.js'                                                    => 'templates/starter/vite.config.js.stub',
                'tailwind.config.js'                                                => 'templates/starter/tailwind.config.js.stub',
                'postcss.config.js'                                                 => 'templates/starter/postcss.config.js.stub',
                'src/Web/Providers/WebServiceProvider.php'                          => 'templates/starter/provider.php.stub',
                'src/Web/Config/web.php'                                            => 'templates/starter/config.php.stub',
                'src/Web/Http/Middleware/AuthenticateWeb.php'                       => 'templates/starter/middleware_auth.php.stub',
                'src/Web/Http/Controllers/HomeController.php'                       => 'templates/starter/controller_home.php.stub',
                'src/Web/Http/Controllers/PageController.php'                       => 'templates/starter/controller_page.php.stub',
                'src/Web/Http/Controllers/AccountController.php'                    => 'templates/starter/controller_account.php.stub',
                'src/Web/Routes/web.php'                                            => 'templates/starter/routes_web.php.stub',
                'src/Web/Resources/lang/en/app.php'                                 => 'templates/starter/lang_en.php.stub',
                'src/Web/Resources/lang/ar/app.php'                                 => 'templates/starter/lang_ar.php.stub',
                'src/Web/Resources/views/components/layouts/index.blade.php'         => 'templates/starter/layout.blade.php.stub',
                'src/Web/Resources/views/components/layouts/header/index.blade.php'  => 'templates/starter/header.blade.php.stub',
                'src/Web/Resources/views/components/layouts/header/navbar.blade.php' => 'templates/starter/navbar.blade.php.stub',
                'src/Web/Resources/views/components/layouts/footer/index.blade.php'  => 'templates/starter/footer.blade.php.stub',
                'src/Web/Resources/views/components/container/index.blade.php'       => 'templates/starter/component_container.blade.php.stub',
                'src/Web/Resources/views/components/section/index.blade.php'         => 'templates/starter/component_section.blade.php.stub',
                'src/Web/Resources/views/components/card/index.blade.php'            => 'templates/starter/component_card.blade.php.stub',
                'src/Web/Resources/views/components/button/index.blade.php'          => 'templates/starter/component_button.blade.php.stub',
                'src/Web/Resources/views/components/modal/index.blade.php'           => 'templates/starter/component_modal.blade.php.stub',
                'src/Web/Resources/views/components/form/control-group/index.blade.php' => 'templates/starter/component_form_control_group.blade.php.stub',
                'src/Web/Resources/views/home/index.blade.php'                      => 'templates/starter/view_home.blade.php.stub',
                'src/Web/Resources/views/pages/show.blade.php'                      => 'templates/starter/view_page.blade.php.stub',
                'src/Web/Resources/views/account/dashboard.blade.php'               => 'templates/starter/view_account_dashboard.blade.php.stub',
                'src/Web/Resources/assets/css/app.css'                              => 'templates/starter/asset_css.css.stub',
                'src/Web/Resources/assets/js/app.js'                               => 'templates/starter/asset_js.js.stub',
                'tests/Feature/Web/WebPageTest.php'                                 => 'templates/starter/feature_test.php.stub',
            ],
        ],
    ];

    /**
     * Get all registered templates.
     *
     * @return array<string, array{id: string, name: string, description: string, files: array<string, string>}>
     */
    public static function all(): array
    {
        return static::$templates;
    }

    /**
     * Get template IDs list.
     *
     * @return list<string>
     */
    public static function getAvailableTemplateIds(): array
    {
        return array_keys(static::$templates);
    }

    /**
     * Check if a template ID is registered.
     */
    public static function has(string $templateId): bool
    {
        return isset(static::$templates[$templateId]);
    }

    /**
     * Get a template definition by ID.
     *
     * @return array{id: string, name: string, description: string, files: array<string, string>}
     *
     * @throws PackageGenerationException
     */
    public static function get(string $templateId): array
    {
        if (! static::has($templateId)) {
            $available = implode(', ', static::getAvailableTemplateIds());
            throw PackageGenerationException::invalidInput(
                "Invalid template [{$templateId}]. Available templates: {$available}"
            );
        }

        return static::$templates[$templateId];
    }

    /**
     * Get default template ID.
     */
    public static function defaultTemplateId(): string
    {
        return static::DEFAULT_TEMPLATE;
    }
}
