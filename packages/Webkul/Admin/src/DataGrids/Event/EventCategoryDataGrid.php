<?php

namespace Webkul\Admin\DataGrids\Event;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class EventCategoryDataGrid extends DataGrid
{
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('event_categories')
            ->select(
                'id',
                'name',
                'description',
                'sort_order',
                'status'
            );

        $this->addFilter('id', 'event_categories.id');
        $this->addFilter('name', 'event_categories.name');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.event-categories.index.datagrid.id'),
            'type' => 'integer',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.event-categories.index.datagrid.name'),
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'description',
            'label' => trans('admin::app.event-categories.index.datagrid.description'),
            'type' => 'string',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'sort_order',
            'label' => trans('admin::app.event-categories.index.datagrid.sort_order'),
            'type' => 'integer',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('admin::app.event-categories.index.datagrid.status'),
            'type' => 'boolean',
            'sortable' => true,
            'closure' => function ($row) {
                return $row->status ? '<span class="badge badge-md badge-success">'.trans('admin::app.event-categories.index.datagrid.active').'</span>' : '<span class="badge badge-md badge-danger">'.trans('admin::app.event-categories.index.datagrid.inactive').'</span>';
            },
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('events.categories.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('admin::app.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.events.categories.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('events.categories.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('admin::app.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.events.categories.delete', $row->id),
            ]);
        }
    }
}
