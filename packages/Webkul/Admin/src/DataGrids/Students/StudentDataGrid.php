<?php

namespace Webkul\Admin\DataGrids\Students;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class StudentDataGrid extends DataGrid
{
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('students')
            ->leftJoin('event_student', 'students.id', '=', 'event_student.student_id')
            ->select(
                'students.id',
                'students.name',
                'students.university_card_number',
                'students.registration_number',
                'students.major',
                'students.academic_level',
                DB::raw('COUNT(event_student.event_id) as subscribed_events_count'),
                'students.created_at'
            )
            ->groupBy(
                'students.id',
                'students.name',
                'students.university_card_number',
                'students.registration_number',
                'students.major',
                'students.academic_level',
                'students.created_at'
            );

        $this->addFilter('id', 'students.id');
        $this->addFilter('name', 'students.name');
        $this->addFilter('university_card_number', 'students.university_card_number');
        $this->addFilter('registration_number', 'students.registration_number');
        $this->addFilter('major', 'students.major');
        $this->addFilter('academic_level', 'students.academic_level');
        $this->addFilter('created_at', 'students.created_at');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.students.index.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('admin::app.students.index.datagrid.name'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'university_card_number',
            'label' => trans('admin::app.students.index.datagrid.university-card-number'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'registration_number',
            'label' => trans('admin::app.students.index.datagrid.registration-number'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'searchable' => true,
            'closure' => fn ($row) => $row->registration_number ?: '—',
        ]);

        $this->addColumn([
            'index' => 'major',
            'label' => trans('admin::app.students.index.datagrid.major'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'searchable' => true,
            'closure' => fn ($row) => $row->major ?: '—',
        ]);

        $this->addColumn([
            'index' => 'academic_level',
            'label' => trans('admin::app.students.index.datagrid.academic-level'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'searchable' => true,
            'closure' => fn ($row) => $row->academic_level ?: '—',
        ]);

        $this->addColumn([
            'index' => 'subscribed_events_count',
            'label' => trans('admin::app.students.index.datagrid.subscribed-events-count'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'searchable' => false,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.students.index.datagrid.created-at'),
            'type' => 'datetime',
            'filterable' => true,
            'sortable' => true,
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('students.view')) {
            $this->addAction([
                'icon' => 'icon-eye',
                'title' => trans('admin::app.students.index.datagrid.view'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.students.view', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('students.edit')) {
            $this->addAction([
                'icon' => 'icon-edit',
                'title' => trans('admin::app.students.index.datagrid.edit'),
                'method' => 'GET',
                'url' => fn ($row) => route('admin.students.edit', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('students.delete')) {
            $this->addAction([
                'icon' => 'icon-delete',
                'title' => trans('admin::app.students.index.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => route('admin.students.delete', $row->id),
            ]);
        }
    }

    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('students.delete')) {
            $this->addMassAction([
                'icon' => 'icon-delete',
                'title' => trans('admin::app.students.index.datagrid.delete'),
                'method' => 'POST',
                'url' => route('admin.students.mass_delete'),
            ]);
        }
    }
}
