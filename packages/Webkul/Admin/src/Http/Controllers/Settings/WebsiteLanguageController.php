<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Admin\DataGrids\Settings\WebsiteLanguageDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Contracts\ContentLocaleManager;

class WebsiteLanguageController extends Controller
{
    public function __construct(protected ContentLocaleManager $languages) {}

    public function index(): View|JsonResponse|BinaryFileResponse
    {
        $this->assertReady();

        if (request()->ajax()) {
            return datagrid(WebsiteLanguageDataGrid::class)->process();
        }

        return view('admin::settings.website-languages.index', [
            'primary' => $this->languages->primaryContentLocale(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->assertReady();

        $locale = $this->languages->createLocale($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return new JsonResponse([
                'data' => [
                    'id'         => $locale->id,
                    'code'       => $locale->code,
                    'name'       => $locale->name,
                    'direction'  => $locale->direction->value,
                    'sort_order' => $locale->sort_order,
                    'is_active'  => (bool) $locale->is_active,
                ],
                'message' => trans('admin::website-languages.saved'),
            ]);
        }

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $this->assertReady();

        $locale = $this->languages->updateMetadata($id, $request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return new JsonResponse([
                'data' => [
                    'id'         => $locale->id,
                    'code'       => $locale->code,
                    'name'       => $locale->name,
                    'direction'  => $locale->direction->value,
                    'sort_order' => $locale->sort_order,
                    'is_active'  => (bool) $locale->is_active,
                ],
                'message' => trans('admin::website-languages.saved'),
            ]);
        }

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    public function activate(int $id): JsonResponse|RedirectResponse
    {
        $this->assertReady();

        $locale = $this->languages->activate($id);

        if (request()->ajax() || request()->wantsJson()) {
            return new JsonResponse([
                'data' => [
                    'id'        => $locale->id,
                    'is_active' => (bool) $locale->is_active,
                ],
                'message' => trans('admin::website-languages.saved'),
            ]);
        }

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    public function deactivate(int $id): JsonResponse|RedirectResponse
    {
        $this->assertReady();

        try {
            $locale = $this->languages->deactivate($id);
        } catch (DomainException $exception) {
            $message = $exception->getMessage() === 'The last active content locale cannot be deactivated.'
                ? trans('admin::website-languages.cannot-deactivate-last-active')
                : trans('admin::website-languages.cannot-deactivate-primary');

            if (request()->ajax() || request()->wantsJson()) {
                return new JsonResponse([
                    'message' => $message,
                ], 422);
            }

            return back()->withErrors(['language' => $message]);
        }

        if (request()->ajax() || request()->wantsJson()) {
            return new JsonResponse([
                'data' => [
                    'id'        => $locale->id,
                    'is_active' => (bool) $locale->is_active,
                ],
                'message' => trans('admin::website-languages.saved'),
            ]);
        }

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    public function primary(int $id): JsonResponse|RedirectResponse
    {
        $this->assertReady();

        $locale = $this->languages->changePrimary($id);

        if (request()->ajax() || request()->wantsJson()) {
            return new JsonResponse([
                'data' => [
                    'id'        => $locale->id,
                    'is_active' => (bool) $locale->is_active,
                ],
                'message' => trans('admin::website-languages.saved'),
            ]);
        }

        return back()->with('success', trans('admin::website-languages.saved'));
    }

    private function assertReady(): void
    {
        abort_unless(Schema::hasTable('locales') && Schema::hasTable('content_locale_settings'), 503);
    }
}
