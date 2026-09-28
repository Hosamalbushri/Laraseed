<?php

namespace Webkul\LostAndFound\DataGrids\Employee;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Webkul\DataGrid\DataGrid;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Services\Application\LostAndFoundAuthorization;

class ClaimDataGrid extends DataGrid
{
    private const SORT_COLUMNS = [
        'id' => 'lost_found_claims.id',
        'status' => 'lost_found_claims.status',
        'submitted_at' => 'lost_found_claims.submitted_at',
    ];

    public function prepareQueryBuilder(): Builder
    {
        LostAndFoundAuthorization::authorizeUser(auth('user')->user(), 'lost_found.claims.view');

        $itemId = (int) request()->route('id');

        return DB::table('lost_found_claims')
            ->join('students', 'lost_found_claims.claimant_student_id', '=', 'students.id')
            ->join('lost_found_items', 'lost_found_claims.found_item_id', '=', 'lost_found_items.id')
            ->where('lost_found_claims.found_item_id', $itemId)
            ->select([
                'lost_found_claims.id',
                'lost_found_claims.status',
                'lost_found_claims.submitted_at',
                'students.id as claimant_student_id',
                'students.name as claimant_name',
            ])
            ->selectRaw('CASE WHEN lost_found_items.approved_claim_id = lost_found_claims.id THEN 1 ELSE 0 END as is_approved_claim')
            ->selectSub(DB::table('lost_found_claim_evidence')
                ->selectRaw('COUNT(*)')
                ->whereColumn('claim_id', 'lost_found_claims.id')
                ->where('evidence_type', '!=', EvidenceType::IMAGE_ATTACHMENT->value), 'text_evidence_count')
            ->selectSub(DB::table('lost_found_claim_evidence')
                ->selectRaw('COUNT(*)')
                ->whereColumn('claim_id', 'lost_found_claims.id')
                ->where('evidence_type', EvidenceType::IMAGE_ATTACHMENT->value), 'image_evidence_count');
    }

    public function prepareColumns(): void
    {
        foreach (['id', 'status', 'claimant_student_id', 'claimant_name', 'submitted_at', 'text_evidence_count', 'image_evidence_count', 'is_approved_claim'] as $index) {
            $column = [
                'index' => $index,
                'label' => trans("lost_found::app.employee.claims.{$index}"),
                'type' => in_array($index, ['id', 'claimant_student_id', 'text_evidence_count', 'image_evidence_count'], true) ? 'integer' : 'string',
                'sortable' => isset(self::SORT_COLUMNS[$index]),
                'filterable' => $index === 'status',
            ];

            if ($index === 'status') {
                $column['filterable_type'] = 'dropdown';
                $column['filterable_options'] = array_map(static fn (ClaimStatus $status): array => [
                    'label' => trans("lost_found::app.employee.claims.statuses.{$status->value}"),
                    'value' => $status->value,
                ], ClaimStatus::cases());
                $column['closure'] = static fn ($row): string => e(trans("lost_found::app.employee.claims.statuses.{$row->status}"));
            }

            $this->addColumn($column);
        }

        $this->addColumn([
            'index' => 'detail_link',
            'label' => trans('lost_found::app.employee.claims.view_detail'),
            'type' => 'string',
            'closure' => static fn ($row): string => '<a href="'.e(route('admin.lost_found.claims.show', (int) $row->id)).'">'.e(trans('lost_found::app.employee.claims.view_detail')).'</a>',
        ]);
    }

    protected function validatedRequest(): array
    {
        return request()->validate([
            'filters' => ['sometimes', 'array:status'],
            'filters.status' => ['sometimes', 'array', 'max:6'],
            'filters.status.*' => ['string', Rule::in(array_column(ClaimStatus::cases(), 'value'))],
            'sort' => ['sometimes', 'array:column,order'],
            'sort.column' => ['required_with:sort.order', Rule::in(array_keys(self::SORT_COLUMNS))],
            'sort.order' => ['required_with:sort.column', Rule::in(['asc', 'desc'])],
            'pagination' => ['sometimes', 'array:page,per_page'],
            'pagination.page' => ['sometimes', 'integer', 'min:1'],
            'pagination.per_page' => ['sometimes', 'integer', Rule::in($this->perPageOptions)],
            'export' => ['prohibited'],
            'format' => ['prohibited'],
        ]);
    }

    protected function processRequestedFilters(array $requestedFilters): Builder
    {
        if ($requestedFilters['status'] ?? []) {
            $this->queryBuilder->whereIn('lost_found_claims.status', $requestedFilters['status']);
        }

        return $this->queryBuilder;
    }

    protected function processRequestedSorting($requestedSort): Builder
    {
        return $this->queryBuilder->orderBy(self::SORT_COLUMNS[$requestedSort['column'] ?? 'id'], $requestedSort['order'] ?? 'desc');
    }

    protected function sanitizeRow($row): \stdClass
    {
        foreach ($row as $field => $value) {
            if (is_string($value)) {
                $row->{$field} = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }

        return $row;
    }
}
