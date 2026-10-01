<?php

namespace Laraseed\Contacts\Admin\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ContactDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('contacts')
            ->addSelect(
                'contacts.id',
                'contacts.name as canonical_name',
                'contacts.type',
                'contacts.email',
                'contacts.phone',
                'contacts.is_active',
                'contacts.created_at'
            );

        $this->addFilter('id', 'contacts.id');
        $this->addFilter('canonical_name', 'contacts.name');
        $this->addFilter('name', 'contacts.name');
        $this->addFilter('type', 'contacts.type');
        $this->addFilter('email', 'contacts.email');
        $this->addFilter('phone', 'contacts.phone');
        $this->addFilter('is_active', 'contacts.is_active');
        $this->addFilter('created_at', 'contacts.created_at');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('contacts_admin::app.admin.datagrid.id'),
            'type'       => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'canonical_name',
            'label'      => trans('contacts_admin::app.admin.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'type',
            'label'              => trans('contacts_admin::app.admin.datagrid.type'),
            'type'               => 'string',
            'searchable'         => true,
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('contacts_admin::app.admin.types.person'),
                    'value' => 'person',
                ],
                [
                    'label' => trans('contacts_admin::app.admin.types.organization'),
                    'value' => 'organization',
                ],
            ],
            'sortable'           => true,
            'closure'            => fn ($row) => $row->type === 'person'
                ? trans('contacts_admin::app.admin.types.person')
                : trans('contacts_admin::app.admin.types.organization'),
        ]);

        $this->addColumn([
            'index'      => 'email',
            'label'      => trans('contacts_admin::app.admin.datagrid.email'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->email ?: '-',
        ]);

        $this->addColumn([
            'index'      => 'phone',
            'label'      => trans('contacts_admin::app.admin.datagrid.phone'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => $row->phone ?: '-',
        ]);

        $this->addColumn([
            'index'              => 'is_active',
            'label'              => trans('contacts_admin::app.admin.datagrid.status'),
            'type'               => 'boolean',
            'searchable'         => false,
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('contacts_admin::app.admin.status.active'),
                    'value' => 1,
                ],
                [
                    'label' => trans('contacts_admin::app.admin.status.inactive'),
                    'value' => 0,
                ],
            ],
            'sortable'           => true,
            'closure'            => fn ($row) => (bool) $row->is_active
                ? trans('contacts_admin::app.admin.status.active')
                : trans('contacts_admin::app.admin.status.inactive'),
        ]);

        $this->addColumn([
            'index'           => 'created_at',
            'label'           => trans('contacts_admin::app.admin.datagrid.created_at'),
            'type'            => 'date',
            'searchable'      => true,
            'filterable'      => true,
            'filterable_type' => 'date_range',
            'sortable'        => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('contacts')) {
            $this->addAction([
                'index'  => 'view',
                'icon'   => 'icon-eye',
                'title'  => trans('contacts_admin::app.admin.datagrid.view'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.contacts.show', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('contacts.edit')) {
            $this->addAction([
                'index'  => 'edit',
                'icon'   => 'icon-edit',
                'title'  => trans('contacts_admin::app.admin.datagrid.edit'),
                'method' => 'GET',
                'url'    => fn ($row) => route('admin.contacts.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('contacts.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('contacts_admin::app.admin.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => fn ($row) => route('admin.contacts.destroy', $row->id),
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('contacts.delete')) {
            $this->addMassAction([
                'icon'   => 'icon-delete',
                'title'  => trans('contacts_admin::app.admin.datagrid.delete'),
                'method' => 'POST',
                'url'    => route('admin.contacts.mass_destroy'),
            ]);
        }

        if (bouncer()->hasPermission('contacts.edit')) {
            $this->addMassAction([
                'title'   => trans('contacts_admin::app.admin.datagrid.update_status'),
                'method'  => 'POST',
                'url'     => route('admin.contacts.mass_update'),
                'options' => [
                    [
                        'label' => trans('contacts_admin::app.admin.status.active'),
                        'value' => 1,
                    ],
                    [
                        'label' => trans('contacts_admin::app.admin.status.inactive'),
                        'value' => 0,
                    ],
                ],
            ]);
        }
    }
}
