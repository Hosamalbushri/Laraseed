<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Webkul\Theme\Contracts\ThemeRegistryContract;
use Webkul\Theme\Definitions\ThemeDefinition;
use Webkul\Theme\View\ThemeViewFinder;

it('renders a semantic Button component with default button type and attributes', function () {
    $rendered = Blade::render('<x-web::button>Click Me</x-web::button>');

    expect($rendered)->toContain('<button')
        ->toContain('type="button"')
        ->toContain('class="web-button web-button--primary web-button--md"')
        ->toContain('Click Me')
        ->toContain('</button>');
});

it('renders Button with custom type, variant, size, and disabled state', function () {
    $rendered = Blade::render('<x-web::button type="submit" variant="danger" size="lg" disabled>Delete</x-web::button>');

    expect($rendered)->toContain('<button')
        ->toContain('type="submit"')
        ->toContain('class="web-button web-button--danger web-button--lg"')
        ->toContain('disabled')
        ->toContain('Delete');
});

it('renders Button as link when href is supplied with accessible role="button"', function () {
    $rendered = Blade::render('<x-web::button href="https://example.com" target="_blank" variant="outline">Visit</x-web::button>');

    expect($rendered)->toContain('<a')
        ->toContain('href="https://example.com"')
        ->toContain('role="button"')
        ->toContain('target="_blank"')
        ->toContain('class="web-button web-button--outline web-button--md"')
        ->toContain('Visit')
        ->toContain('</a>');
});

it('renders disabled link Button with aria-disabled and tabindex="-1"', function () {
    $rendered = Blade::render('<x-web::button href="/disabled-link" :disabled="true">Disabled Link</x-web::button>');

    expect($rendered)->toContain('<a')
        ->toContain('aria-disabled="true"')
        ->toContain('tabindex="-1"')
        ->toContain('is-disabled');
});

it('validates Button variants and falls back safely to primary for unknown variants', function () {
    $rendered = Blade::render('<x-web::button variant="unknown_hack_variant">Fallback</x-web::button>');

    expect($rendered)->toContain('web-button--primary')
        ->not->toContain('unknown_hack_variant');
});

it('renders Card structural component with header, content, and footer', function () {
    $rendered = Blade::render('
        <x-web::card as="article" id="custom-card">
            <x-slot:header>Card Header</x-slot:header>
            Main Body Content
            <x-slot:footer>Card Footer</x-slot:footer>
        </x-web::card>
    ');

    expect($rendered)->toContain('<article')
        ->toContain('id="custom-card"')
        ->toContain('class="web-card"')
        ->toContain('<header class="web-card__header">')
        ->toContain('Card Header')
        ->toContain('<div class="web-card__content">')
        ->toContain('Main Body Content')
        ->toContain('<footer class="web-card__footer">')
        ->toContain('Card Footer')
        ->toContain('</article>');
});

it('renders Card subcomponents independently', function () {
    $rendered = Blade::render('
        <x-web::card>
            <x-web::card.header>Section Header</x-web::card.header>
            <x-web::card.content>Section Content</x-web::card.content>
            <x-web::card.footer>Section Footer</x-web::card.footer>
        </x-web::card>
    ');

    expect($rendered)->toContain('web-card__header')
        ->toContain('Section Header')
        ->toContain('web-card__content')
        ->toContain('Section Content')
        ->toContain('web-card__footer')
        ->toContain('Section Footer');
});

it('renders Badge with allowed variants and size', function () {
    $rendered = Blade::render('<x-web::badge variant="success" size="sm">Active</x-web::badge>');

    expect($rendered)->toContain('<span')
        ->toContain('class="web-badge web-badge--success web-badge--sm"')
        ->toContain('Active')
        ->toContain('</span>');
});

it('falls back Badge to neutral for unknown variant', function () {
    $rendered = Blade::render('<x-web::badge variant="invalid_badge">Fallback</x-web::badge>');

    expect($rendered)->toContain('web-badge--neutral')
        ->not->toContain('invalid_badge');
});

it('renders Alert with role="status" for info and role="alert" for danger', function () {
    $infoAlert = Blade::render('<x-web::alert variant="info" title="Note">Info message</x-web::alert>');
    $dangerAlert = Blade::render('<x-web::alert variant="danger" title="Error">Error message</x-web::alert>');

    expect($infoAlert)->toContain('role="status"')
        ->toContain('web-alert--info')
        ->toContain('Note')
        ->toContain('Info message');

    expect($dangerAlert)->toContain('role="alert"')
        ->toContain('web-alert--danger')
        ->toContain('Error')
        ->toContain('Error message');
});

it('renders dismissible Alert with accessible close button', function () {
    $rendered = Blade::render('<x-web::alert :dismissible="true">Dismissible message</x-web::alert>');

    expect($rendered)->toContain('data-web-alert')
        ->toContain('is-dismissible')
        ->toContain('data-web-alert-dismiss')
        ->toContain('web-alert__close')
        ->toContain('aria-label=');
});

it('renders Form Field with label, help text, error, and accessible associations', function () {
    $rendered = Blade::render('
        <x-web::form.field
            name="user_email"
            id="user_email_id"
            label="Email Address"
            :required="true"
            help="We will never share your email."
            error="Please enter a valid email address."
        >
            <x-web::form.input
                name="user_email"
                id="user_email_id"
                type="email"
                :required="true"
                :invalid="true"
                describedBy="user_email_id-help user_email_id-error"
            />
        </x-web::form.field>
    ');

    expect($rendered)->toContain('<label for="user_email_id" class="web-field__label">')
        ->toContain('Email Address')
        ->toContain('aria-hidden="true">*</span>')
        ->toContain('<p id="user_email_id-help" class="web-field__help">')
        ->toContain('We will never share your email.')
        ->toContain('<p id="user_email_id-error" class="web-field__error" role="alert">')
        ->toContain('Please enter a valid email address.')
        ->toContain('<input')
        ->toContain('type="email"')
        ->toContain('name="user_email"')
        ->toContain('id="user_email_id"')
        ->toContain('aria-invalid="true"')
        ->toContain('aria-describedby="user_email_id-help user_email_id-error"');
});

it('renders Form Field with auto-generated collision-safe IDs when id is omitted', function () {
    $rendered1 = Blade::render('<x-web::form.field label="Field 1" help="Help 1"><input /></x-web::form.field>');
    $rendered2 = Blade::render('<x-web::form.field label="Field 2" help="Help 2"><input /></x-web::form.field>');

    preg_match('/for="([^"]+)"/', $rendered1, $matches1);
    preg_match('/for="([^"]+)"/', $rendered2, $matches2);

    expect($matches1[1])->not->toBeEmpty();
    expect($matches2[1])->not->toBeEmpty();
    expect($matches1[1])->not->toBe($matches2[1]);
});

it('renders Accordion and Items with correct ARIA roles and progressive enhancement attributes', function () {
    $rendered = Blade::render('
        <x-web::accordion id="faq-accordion" :alwaysOpen="true">
            <x-web::accordion.item id="faq-item-1" title="Question 1" :expanded="true">
                Answer 1
            </x-web::accordion.item>
            <x-web::accordion.item id="faq-item-2" title="Question 2" :expanded="false">
                Answer 2
            </x-web::accordion.item>
        </x-web::accordion>
    ');

    expect($rendered)->toContain('id="faq-accordion"')
        ->toContain('data-web-accordion')
        ->toContain('data-web-accordion-always-open="true"')
        ->toContain('id="faq-item-1-trigger"')
        ->toContain('aria-expanded="true"')
        ->toContain('aria-controls="faq-item-1-panel"')
        ->toContain('data-web-accordion-trigger')
        ->toContain('id="faq-item-1-panel"')
        ->toContain('role="region"')
        ->toContain('aria-labelledby="faq-item-1-trigger"')
        ->toContain('data-web-accordion-panel')
        ->toContain('Answer 1')
        ->toContain('id="faq-item-2-trigger"')
        ->toContain('aria-expanded="false"')
        ->toContain('aria-controls="faq-item-2-panel"')
        ->toContain('id="faq-item-2-panel"')
        ->toContain('hidden')
        ->toContain('Answer 2');
});

it('renders Modal component with accessible ARIA dialog attributes and slots', function () {
    $rendered = Blade::render('
        <x-web::modal id="test-modal" title="Test Modal Title" size="lg">
            <x-slot:trigger>
                <x-web::button>Open</x-web::button>
            </x-slot:trigger>
            Modal Body Content
            <x-slot:footer>
                <button type="button">Cancel</button>
            </x-slot:footer>
        </x-web::modal>
    ');

    expect($rendered)->toContain('<v-web-modal')
        ->toContain('id="test-modal"')
        ->toContain('data-web-modal-trigger="test-modal"')
        ->toContain('id="test-modal-dialog"')
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"')
        ->toContain('aria-labelledby="test-modal-title"')
        ->toContain('Test Modal Title')
        ->toContain('data-web-modal-backdrop')
        ->toContain('data-web-modal-close')
        ->toContain('Modal Body Content')
        ->toContain('Cancel');
});

it('renders Drawer component with accessible slideout structure and placement', function () {
    $rendered = Blade::render('
        <x-web::drawer id="test-drawer" title="Drawer Title" placement="end">
            <x-slot:trigger>
                <x-web::button>Open Drawer</x-web::button>
            </x-slot:trigger>
            Drawer Body Content
        </x-web::drawer>
    ');

    expect($rendered)->toContain('<v-web-drawer')
        ->toContain('id="test-drawer"')
        ->toContain('placement="end"')
        ->toContain('data-web-drawer-trigger="test-drawer"')
        ->toContain('id="test-drawer-dialog"')
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"')
        ->toContain('aria-labelledby="test-drawer-title"')
        ->toContain('Drawer Title')
        ->toContain('data-web-drawer-backdrop')
        ->toContain('data-web-drawer-close')
        ->toContain('Drawer Body Content');
});

it('renders Dropdown component with trigger and popover menu structure', function () {
    $rendered = Blade::render('
        <x-web::dropdown id="test-dropdown" align="end">
            <x-slot:trigger>
                <button>Trigger</button>
            </x-slot:trigger>
            <a href="#test">Link Item</a>
        </x-web::dropdown>
    ');

    expect($rendered)->toContain('<v-web-dropdown')
        ->toContain('id="test-dropdown"')
        ->toContain('data-web-dropdown-trigger')
        ->toContain('id="test-dropdown-menu"')
        ->toContain('data-web-dropdown-menu')
        ->toContain('Link Item');
});

it('ensures Web component files contain zero references to forbidden packages or Admin', function () {
    $componentPath = base_path('packages/Webkul/Web/src/Resources/views/components');

    $forbiddenTerms = [
        'Webkul\\Admin',
        'admin::',
        'x-admin',
        'Webkul\\Student',
        'Webkul\\Event',
        'Webkul\\LostAndFound',
        'Webkul\\Shop',
    ];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($componentPath));
    $scannedFiles = 0;

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $scannedFiles++;
            $content = file_get_contents($file->getPathname());

            foreach ($forbiddenTerms as $term) {
                expect(str_contains($content, $term))
                    ->toBeFalse("Component file {$file->getPathname()} contains forbidden reference: {$term}");
            }
        }
    }

    expect($scannedFiles)->toBeGreaterThan(8);
});

it('ensures Web components perform zero database queries or business authorization checks', function () {
    $componentPath = base_path('packages/Webkul/Web/src/Resources/views/components');

    $forbiddenPatterns = [
        'DB::',
        '::query(',
        '->where(',
        'bouncer()',
        'auth()->user()->hasPermission',
        'Bouncer::',
    ];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($componentPath));
    $scannedFiles = 0;

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $scannedFiles++;
            $content = file_get_contents($file->getPathname());

            foreach ($forbiddenPatterns as $pattern) {
                expect(str_contains($content, $pattern))
                    ->toBeFalse("Component file {$file->getPathname()} contains forbidden query/auth pattern: {$pattern}");
            }
        }
    }

    expect($scannedFiles)->toBeGreaterThan(8);
});
