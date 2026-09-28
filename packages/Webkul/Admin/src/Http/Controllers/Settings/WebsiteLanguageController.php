<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Models\Locale;
use Webkul\Core\Services\ContentLocaleService;

class WebsiteLanguageController extends Controller
{
    public function __construct(protected ContentLocaleService $languages) {}

    public function index(): View
    {
        $this->assertReady();

        return view('admin::settings.website-languages.index', [
            'languages' => $this->languages->allContentLocales(),
            'primary' => $this->languages->primaryContentLocale(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $this->assertReady();

        $data = request()->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/\A[a-z]{2,3}(?:[-_](?:[a-z]{2}|[0-9]{3}))?\z/iD'],
            'name' => ['required', 'string', 'max:120'],
            'direction' => ['required', Rule::in(['ltr', 'rtl'])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
        $data['code'] = Locale::normalizeCode($data['code']);
        validator($data, ['code' => Rule::unique('locales', 'code')])->validate();
        $data['is_active'] = false;
        $this->languages->create($data);

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    public function update(int $id): RedirectResponse
    {
        $this->assertReady();

        $data = request()->validate([
            'name' => ['required', 'string', 'max:120'],
            'direction' => ['required', Rule::in(['ltr', 'rtl'])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
        $this->languages->updateMetadata($id, $data);

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    public function activate(int $id): RedirectResponse
    {
        $this->assertReady();

        $this->languages->activate($id);

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    public function deactivate(int $id): RedirectResponse
    {
        $this->assertReady();

        try {
            $this->languages->deactivate($id);
        } catch (DomainException $exception) {
            return back()->withErrors(['language' => trans('admin::website-languages.cannot-deactivate-primary')]);
        }

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    public function primary(int $id): RedirectResponse
    {
        $this->assertReady();

        $this->languages->changePrimary($id);

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    private function assertReady(): void
    {
        abort_unless(Schema::hasTable('locales') && Schema::hasTable('content_locale_settings'), 503);
    }
}
