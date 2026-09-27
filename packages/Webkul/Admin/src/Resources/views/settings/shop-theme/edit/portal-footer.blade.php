@php
    use Illuminate\Support\Facades\Storage;

    $e = array_merge([
        'orbs' => true, 'grid' => true, 'parallax' => true, 'font_awesome' => true, 'back_to_top' => true,
    ], $opts['effects'] ?? []);
    $c = array_merge([
        'bg_start' => '#0a0a2a', 'bg_end' => '#030318', 'accent' => '#8b5cf6', 'accent_2' => '#6366f1',
        'border_top' => 'rgba(139, 92, 246, 0.2)', 'text' => '#ffffff', 'text_muted' => 'rgba(255,255,255,0.6)',
        'orb_1' => 'rgba(139, 92, 246, 0.6)', 'orb_2' => 'rgba(236, 72, 153, 0.5)', 'orb_3' => 'rgba(59, 130, 246, 0.4)',
    ], $opts['colors'] ?? []);
    $brand = array_merge(['logo_path' => '', 'logo_icon' => 'fas fa-graduation-cap', 'title' => '', 'description' => ''], $opts['brand'] ?? []);
    $brandLogoPath = trim((string) ($brand['logo_path'] ?? ''));
    $brandLogoPreview = $brandLogoPath !== '' && Storage::disk('public')->exists($brandLogoPath)
        ? Storage::url($brandLogoPath)
        : null;
    $social = $opts['social'] ?? [];
    while (count($social) < 8) {
        $social[] = ['icon' => '', 'url' => ''];
    }
    $cq = array_merge(['title' => '', 'show_chevron' => true, 'links' => []], $opts['col_quick'] ?? []);
    while (count($cq['links']) < 12) {
        $cq['links'][] = ['label' => '', 'url' => ''];
    }
    $contact = array_merge(['title' => '', 'items' => []], $opts['contact'] ?? []);
    while (count($contact['items']) < 6) {
        $contact['items'][] = ['icon' => '', 'text' => ''];
    }
    $news = array_merge([
        'enabled' => false, 'title' => '', 'text' => '', 'placeholder' => '', 'button_label' => '',
        'form_action' => '', 'form_method' => 'post',
    ], $opts['newsletter'] ?? []);
    $bottom = array_merge(['copyright' => '', 'links' => []], $opts['bottom'] ?? []);
    while (count($bottom['links']) < 10) {
        $bottom['links'][] = ['label' => '', 'url' => ''];
    }
@endphp

<div class="flex flex-col gap-4">
    <div class="rounded-lg border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        @lang('admin::app.settings.shop-theme.edit.portal.info')
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-200">
            <input type="hidden" name="options[enabled]" value="0">
            <input type="checkbox" name="options[enabled]" value="1" class="rounded border-gray-300 text-brandColor dark:border-gray-600 dark:bg-gray-900" @checked(old('options.enabled', $opts['enabled'] ?? true))>
            @lang('admin::app.settings.shop-theme.edit.portal.enabled')
        </label>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.shop-theme.edit.portal.effects')</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach (['orbs' => 'orbs', 'grid' => 'grid', 'parallax' => 'parallax', 'font_awesome' => 'font-awesome', 'back_to_top' => 'back-to-top'] as $k => $lk)
                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                    <input type="hidden" name="options[effects][{{ $k }}]" value="0">
                    <input type="checkbox" name="options[effects][{{ $k }}]" value="1" class="rounded border-gray-300 text-brandColor dark:border-gray-600 dark:bg-gray-900" @checked(old("options.effects.$k", $e[$k] ?? false))>
                    <span>@lang('admin::app.settings.shop-theme.edit.portal.effect.'.$lk)</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.shop-theme.edit.portal.colors')</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                'bg_start' => 'bg-start', 'bg_end' => 'bg-end', 'accent' => 'accent', 'accent_2' => 'accent-2',
                'border_top' => 'border-top', 'text' => 'text', 'text_muted' => 'text-muted',
                'orb_1' => 'orb-1', 'orb_2' => 'orb-2', 'orb_3' => 'orb-3',
            ] as $field => $lk)
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.color.'.$lk)</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" :name="'options[colors]['.$field.']'" :value="old('options.colors.'.$field, $c[$field] ?? '')" />
                </x-admin::form.control-group>
            @endforeach
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.shop-theme.edit.portal.brand')</p>
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="flex flex-col gap-4">
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.logo-image')</x-admin::form.control-group.label>
                    <input type="hidden" name="options[brand][logo_path]" value="{{ old('options.brand.logo_path', $brandLogoPath) }}">
                    <input
                        type="file"
                        name="options[brand][logo_image]"
                        accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                        class="block w-full text-sm text-gray-800 file:me-4 file:rounded-md file:border-0 file:bg-violet-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-violet-700 dark:text-gray-200 dark:file:bg-violet-700"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.shop-theme.edit.portal.logo-image-help')</p>
                </x-admin::form.control-group>
                @if ($brandLogoPreview)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                        <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-400">@lang('admin::app.settings.shop-theme.edit.portal.logo-current')</p>
                        <img src="{{ $brandLogoPreview }}" alt="" class="max-h-20 max-w-full object-contain object-left">
                    </div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                        <input type="checkbox" name="options[brand][remove_logo]" value="1" class="rounded border-gray-300 text-brandColor dark:border-gray-600 dark:bg-gray-900" @checked(old('options.brand.remove_logo'))>
                        @lang('admin::app.settings.shop-theme.edit.portal.remove-logo')
                    </label>
                @endif
            </div>
            <div class="flex flex-col gap-4">
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.logo-icon')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" name="options[brand][logo_icon]" :value="old('options.brand.logo_icon', $brand['logo_icon'] ?? '')" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.shop-theme.edit.portal.logo-icon-help')</p>
                </x-admin::form.control-group>
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.brand-title')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" name="options[brand][title]" :value="old('options.brand.title', $brand['title'] ?? '')" />
                </x-admin::form.control-group>
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.brand-desc')</x-admin::form.control-group.label>
                    <textarea name="options[brand][description]" rows="4" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">{{ old('options.brand.description', $brand['description'] ?? '') }}</textarea>
                </x-admin::form.control-group>
            </div>
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.shop-theme.edit.portal.social')</p>
        @foreach (range(0, 7) as $i)
            <div class="mb-3 grid gap-3 border-b border-gray-100 pb-3 last:mb-0 last:border-0 last:pb-0 dark:border-gray-800 sm:grid-cols-2">
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.social-icon', ['n' => $i + 1])</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" :name="'options[social]['.$i.'][icon]'" :value="old('options.social.'.$i.'.icon', $social[$i]['icon'] ?? '')" />
                </x-admin::form.control-group>
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.social-url', ['n' => $i + 1])</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" :name="'options[social]['.$i.'][url]'" :value="old('options.social.'.$i.'.url', $social[$i]['url'] ?? '')" />
                </x-admin::form.control-group>
            </div>
        @endforeach
    </div>

    @foreach (['col_quick' => $cq] as $colKey => $col)
        <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.shop-theme.edit.portal.'.$colKey)</p>
            <label class="mb-4 flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                <input type="hidden" name="options[{{ $colKey }}][show_chevron]" value="0">
                <input type="checkbox" name="options[{{ $colKey }}][show_chevron]" value="1" class="rounded border-gray-300 text-brandColor dark:border-gray-600 dark:bg-gray-900" @checked(old("options.$colKey.show_chevron", $col['show_chevron'] ?? true))>
                @lang('admin::app.settings.shop-theme.edit.portal.show-chevron')
            </label>
            <x-admin::form.control-group class="!mb-4">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.column-title')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" :name="'options['.$colKey.'][title]'" :value="old('options.'.$colKey.'.title', $col['title'] ?? '')" />
            </x-admin::form.control-group>
            @foreach (range(0, 11) as $i)
                <div class="mb-2 grid gap-2 sm:grid-cols-2">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.control type="text" :name="'options['.$colKey.'][links]['.$i.'][label]'" :value="old('options.'.$colKey.'.links.'.$i.'.label', $col['links'][$i]['label'] ?? '')" :placeholder="trans('admin::app.settings.shop-theme.edit.portal.link-label')" />
                    </x-admin::form.control-group>
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.control type="text" :name="'options['.$colKey.'][links]['.$i.'][url]'" :value="old('options.'.$colKey.'.links.'.$i.'.url', $col['links'][$i]['url'] ?? '')" :placeholder="trans('admin::app.settings.shop-theme.edit.portal.link-url')" />
                    </x-admin::form.control-group>
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.shop-theme.edit.portal.contact')</p>
        <x-admin::form.control-group class="!mb-4">
            <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.column-title')</x-admin::form.control-group.label>
            <x-admin::form.control-group.control type="text" name="options[contact][title]" :value="old('options.contact.title', $contact['title'] ?? '')" />
        </x-admin::form.control-group>
        @foreach (range(0, 5) as $i)
            <div class="mb-3 grid gap-3 sm:grid-cols-2">
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.contact-icon', ['n' => $i + 1])</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" :name="'options[contact][items]['.$i.'][icon]'" :value="old('options.contact.items.'.$i.'.icon', $contact['items'][$i]['icon'] ?? '')" />
                </x-admin::form.control-group>
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.contact-text', ['n' => $i + 1])</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" :name="'options[contact][items]['.$i.'][text]'" :value="old('options.contact.items.'.$i.'.text', $contact['items'][$i]['text'] ?? '')" />
                </x-admin::form.control-group>
            </div>
        @endforeach
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.shop-theme.edit.portal.newsletter')</p>
        <label class="mb-4 flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
            <input type="hidden" name="options[newsletter][enabled]" value="0">
            <input type="checkbox" name="options[newsletter][enabled]" value="1" class="rounded border-gray-300 text-brandColor dark:border-gray-600 dark:bg-gray-900" @checked(old('options.newsletter.enabled', $news['enabled'] ?? false))>
            @lang('admin::app.settings.shop-theme.edit.portal.newsletter-enabled')
        </label>
        <div class="grid gap-4">
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.newsletter-title')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[newsletter][title]" :value="old('options.newsletter.title', $news['title'] ?? '')" />
            </x-admin::form.control-group>
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.newsletter-text')</x-admin::form.control-group.label>
                <textarea name="options[newsletter][text]" rows="3" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">{{ old('options.newsletter.text', $news['text'] ?? '') }}</textarea>
            </x-admin::form.control-group>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.newsletter-placeholder')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" name="options[newsletter][placeholder]" :value="old('options.newsletter.placeholder', $news['placeholder'] ?? '')" />
                </x-admin::form.control-group>
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.newsletter-button')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" name="options[newsletter][button_label]" :value="old('options.newsletter.button_label', $news['button_label'] ?? '')" />
                </x-admin::form.control-group>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.newsletter-action')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="text" name="options[newsletter][form_action]" :value="old('options.newsletter.form_action', $news['form_action'] ?? '')" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.shop-theme.edit.portal.newsletter-action-help')</p>
                </x-admin::form.control-group>
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.newsletter-method')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="select" name="options[newsletter][form_method]" :value="old('options.newsletter.form_method', $news['form_method'] ?? 'post')">
                        <option value="post">POST</option>
                        <option value="get">GET</option>
                    </x-admin::form.control-group.control>
                </x-admin::form.control-group>
            </div>
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.shop-theme.edit.portal.bottom')</p>
        <x-admin::form.control-group class="!mb-4">
            <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.portal.copyright')</x-admin::form.control-group.label>
            <textarea name="options[bottom][copyright]" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">{{ old('options.bottom.copyright', $bottom['copyright'] ?? '') }}</textarea>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.shop-theme.edit.portal.copyright-help')</p>
        </x-admin::form.control-group>
        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">@lang('admin::app.settings.shop-theme.edit.portal.bottom-links')</p>
        @foreach (range(0, 9) as $i)
            <div class="mb-2 grid gap-2 sm:grid-cols-2">
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.control type="text" :name="'options[bottom][links]['.$i.'][label]'" :value="old('options.bottom.links.'.$i.'.label', $bottom['links'][$i]['label'] ?? '')" :placeholder="trans('admin::app.settings.shop-theme.edit.portal.link-label')" />
                </x-admin::form.control-group>
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.control type="text" :name="'options[bottom][links]['.$i.'][url]'" :value="old('options.bottom.links.'.$i.'.url', $bottom['links'][$i]['url'] ?? '')" :placeholder="trans('admin::app.settings.shop-theme.edit.portal.link-url')" />
                </x-admin::form.control-group>
            </div>
        @endforeach
    </div>
</div>
