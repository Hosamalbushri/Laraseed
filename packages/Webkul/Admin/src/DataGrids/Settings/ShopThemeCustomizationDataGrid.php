<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ShopThemeCustomizationDataGrid extends DataGrid
{
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('shop_theme_customizations')
            ->select(
                'shop_theme_customizations.id',
                'shop_theme_customizations.name',
                'shop_theme_customizations.type',
                'shop_theme_customizations.sort_order',
                'shop_theme_customizations.status',
                'shop_theme_customizations.theme_code',
                'shop_theme_customizations.created_at',
            );

        $this->addFilter('id', 'shop_theme_customizations.id');
        $this->addFilter('name', 'shop_theme_customizations.name');
        $this->addFilter('type', 'shop_theme_customizations.type');
        $this->addFilter('sort_order', 'shop_theme_customizations.sort_order');
        $this->addFilter('status', 'shop_theme_customizations.status');
        $this->addFilter('theme_code', 'shop_theme_customizations.theme_code');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.settings.shop-theme.index.datagrid.id'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.settings.shop-theme.index.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'type',
            'label' => trans('admin::app.settings.shop-theme.index.datagrid.type'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $key = 'admin::app.settings.shop-theme.types.'.$row->type;

                return trans($key) !== $key ? trans($key) : $row->type;
            },
        ]);

        $this->addColumn([
            'index' => 'theme_code',
            'label' => trans('admin::app.settings.shop-theme.index.datagrid.theme'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $themes = config('shop.theme_definitions', []);

                return $themes[$row->theme_code]['name'] ?? $row->theme_code;
            },
        ]);

        $this->addColumn([
            'index' => 'sort_order',
            'label' => trans('admin::app.settings.shop-theme.index.datagrid.sort-order'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('admin::app.settings.shop-theme.index.datagrid.status'),
            'type' => 'boolean',
            'searchable' => true,
            'filterable' => true,
            'filterable_options' => [
                [
                    'label' => trans('admin::app.settings.shop-theme.index.datagrid.active'),
                    'value' => 1,
                ],
                [
                    'label' => trans('admin::app.settings.shop-theme.index.datagrid.inactive'),
                    'value' => 0,
                ],
            ],
            'sortable' => true,
            'closure' => function ($value) {
                if ($value->status) {
                    return '<p class="label-active">'.trans('admin::app.settings.shop-theme.index.datagrid.active').'</p>';
                }

                return '<p class="label-pending">'.trans('admin::app.settings.shop-theme.index.datagrid.inactive').'</p>';
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.settings.shop-theme.index.datagrid.created-at'),
            'type' => 'date',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'date_range',
            'closure' => fn ($row) => core()->formatDate($row->created_at),
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('settings.shop_theme.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('admin::app.settings.shop-theme.index.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.settings.shop-theme.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('settings.shop_theme.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('admin::app.settings.shop-theme.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.settings.shop-theme.destroy', $row->id),
            ]);
        }
    }
}
