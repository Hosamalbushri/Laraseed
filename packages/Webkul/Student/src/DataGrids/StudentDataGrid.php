<?php

namespace Webkul\Student\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\DataGrid\DataGrid;

class StudentDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('students')
            ->select(
                'students.id',
                'students.name',
                'students.university_card_number',
                'students.registration_number',
                'students.major',
                'students.academic_level',
                'students.created_at',
            );

        $this->addFilter('id', 'students.id');
        $this->addFilter('name', 'students.name');
        $this->addFilter('university_card_number', 'students.university_card_number');
        $this->addFilter('registration_number', 'students.registration_number');
        $this->addFilter('major', 'students.major');
        $this->addFilter('academic_level', 'students.academic_level');
        $this->addFilter('created_at', 'students.created_at');

        Event::dispatch('admin.students.datagrid.query.after', $queryBuilder);

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('student::app.students.index.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('student::app.students.index.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'university_card_number',
            'label' => trans('student::app.students.index.datagrid.university-card-number'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'registration_number',
            'label' => trans('student::app.students.index.datagrid.registration-number'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'major',
            'label' => trans('student::app.students.index.datagrid.major'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'academic_level',
            'label' => trans('student::app.students.index.datagrid.academic-level'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('student::app.students.index.datagrid.created-at'),
            'type' => 'date',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        Event::dispatch('admin.students.datagrid.columns.after', $this);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('students.view')) {
            $this->addAction([
                'icon' => 'icon-eye',
                'title' => trans('student::app.students.index.datagrid.view'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.students.view', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('students.edit')) {
            $this->addAction([
                'icon' => 'icon-edit',
                'title' => trans('student::app.students.index.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.students.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('students.delete')) {
            $this->addAction([
                'icon' => 'icon-delete',
                'title' => trans('student::app.students.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.students.delete', $row->id),
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('students.delete')) {
            $this->addMassAction([
                'title' => trans('student::app.students.index.datagrid.delete'),
                'url' => route('admin.students.mass_delete'),
                'method' => 'POST',
            ]);
        }
    }
}
