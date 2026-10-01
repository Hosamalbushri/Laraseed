<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WebsiteLanguageDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('locales')
            ->leftJoin('content_locale_settings', 'locales.id', '=', 'content_locale_settings.primary_locale_id')
            ->addSelect(
                'locales.id',
                'locales.code',
                'locales.name',
                'locales.direction',
                'locales.is_active',
                'locales.sort_order',
                'locales.direction as raw_direction',
                'locales.is_active as raw_is_active',
                DB::raw('CASE WHEN content_locale_settings.primary_locale_id IS NOT NULL THEN 1 ELSE 0 END as is_primary'),
                DB::raw('CASE WHEN content_locale_settings.primary_locale_id IS NOT NULL THEN 1 ELSE 0 END as raw_is_primary')
            );

        $this->addFilter('id', 'locales.id');
        $this->addFilter('code', 'locales.code');
        $this->addFilter('name', 'locales.name');
        $this->addFilter('direction', 'locales.direction');
        $this->addFilter('is_active', 'locales.is_active');
        $this->addFilter('sort_order', 'locales.sort_order');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::website-languages.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::website-languages.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::website-languages.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'direction',
            'label'      => trans('admin::website-languages.direction'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->direction === 'rtl'
                ? trans('admin::website-languages.rtl')
                : trans('admin::website-languages.ltr'),
        ]);

        $this->addColumn([
            'index'      => 'is_active',
            'label'      => trans('admin::website-languages.status'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => ($row->raw_is_active ?? $row->is_active)
                ? '<span class="label-active">'.trans('admin::website-languages.active').'</span>'
                : '<span class="label-inactive">'.trans('admin::website-languages.inactive').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'is_primary',
            'label'      => trans('admin::website-languages.primary'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
            'closure'    => fn ($row) => ($row->raw_is_primary ?? $row->is_primary)
                ? '<span class="label-active">'.trans('admin::website-languages.primary').'</span>'
                : '-',
        ]);

        $this->addColumn([
            'index'      => 'sort_order',
            'label'      => trans('admin::website-languages.order'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('settings.website_languages.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('admin::website-languages.edit'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.settings.website-languages.update', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('settings.website_languages.manage')) {
            $this->addAction([
                'index'  => 'activate',
                'icon'   => 'icon-tick',
                'title'  => trans('admin::website-languages.activate'),
                'method' => 'POST',
                'url'    => fn ($row) => route('admin.settings.website-languages.activate', $row->id),
            ]);

            $this->addAction([
                'index'  => 'deactivate',
                'icon'   => 'icon-pause',
                'title'  => trans('admin::website-languages.deactivate'),
                'method' => 'POST',
                'url'    => fn ($row) => route('admin.settings.website-languages.deactivate', $row->id),
            ]);

            $this->addAction([
                'index'  => 'primary',
                'icon'   => 'icon-toast-done',
                'title'  => trans('admin::website-languages.make-primary'),
                'method' => 'POST',
                'url'    => fn ($row) => route('admin.settings.website-languages.primary', $row->id),
            ]);
        }
    }
}
