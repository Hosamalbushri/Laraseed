<?php

namespace Webkul\LostAndFound\DataGrids\Employee;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Webkul\DataGrid\DataGrid;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Services\Application\LostAndFoundAuthorization;
use Webkul\User\Models\User;

class FoundItemDataGrid extends DataGrid
{
    private const SORT_COLUMNS = [
        'id' => 'lost_found_items.id',
        'public_reference' => 'lost_found_items.public_reference',
        'title' => 'lost_found_items.title',
        'found_at' => 'lost_found_items.found_at',
        'status' => 'lost_found_items.status',
        'category_code' => 'lost_found_categories.code',
    ];

    public function prepareQueryBuilder(): Builder
    {
        $actor = $this->actor();
        LostAndFoundAuthorization::authorizeUser($actor, 'lost_found.items.view');

        $query = DB::table('lost_found_items')
            ->leftJoin('lost_found_categories', 'lost_found_items.category_id', '=', 'lost_found_categories.id')
            ->select([
                'lost_found_items.id',
                'lost_found_items.public_reference',
                'lost_found_items.title',
                'lost_found_items.found_location',
                'lost_found_items.found_at',
                'lost_found_items.status',
                'lost_found_categories.code as category_code',
            ]);

        if ($this->can($actor, 'lost_found.claims.view')) {
            $query->selectSub(
                DB::table('lost_found_claims')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('lost_found_claims.found_item_id', 'lost_found_items.id'),
                'claim_count',
            )->selectRaw('CASE WHEN lost_found_items.approved_claim_id IS NULL THEN 0 ELSE 1 END as has_approved_claim');
        }

        if ($this->can($actor, 'lost_found.custody.manage')) {
            $query->leftJoin('users as current_custodian', 'lost_found_items.current_custodian_user_id', '=', 'current_custodian.id')
                ->addSelect('current_custodian.name as current_custodian_name');
        }

        return $query;
    }

    public function prepareColumns(): void
    {
        foreach ([
            ['id', 'integer', false, false, true],
            ['public_reference', 'string', true, false, true],
            ['title', 'string', true, false, true],
            ['category_code', 'string', false, false, true],
            ['found_location', 'string', true, false, false],
            ['found_at', 'date', false, false, true],
            ['status', 'string', false, true, true],
        ] as [$index, $type, $searchable, $filterable, $sortable]) {
            $column = [
                'index' => $index,
                'label' => trans('lost_found::app.employee.items.'.($index === 'title' ? 'title_column' : $index)),
                'type' => $type,
                'searchable' => $searchable,
                'filterable' => $filterable,
                'sortable' => $sortable,
            ];

            if ($index === 'status') {
                $column['filterable_type'] = 'dropdown';
                $column['closure'] = static fn ($row): string => trans("lost_found::app.employee.items.statuses.{$row->status}");
                $column['filterable_options'] = array_map(
                    static fn (ItemStatus $status): array => [
                        'label' => trans("lost_found::app.employee.items.statuses.{$status->value}"),
                        'value' => $status->value,
                    ],
                    ItemStatus::cases(),
                );
            }

            $this->addColumn($column);
        }

        $actor = $this->actor();

        if ($this->can($actor, 'lost_found.claims.view')) {
            foreach (['claim_count' => 'integer', 'has_approved_claim' => 'boolean'] as $index => $type) {
                $this->addColumn([
                    'index' => $index,
                    'label' => trans("lost_found::app.employee.items.{$index}"),
                    'type' => $type,
                ]);
            }

            $this->addColumn([
                'index' => 'claims_link',
                'label' => trans('lost_found::app.employee.claims.view_claims'),
                'type' => 'string',
                'closure' => static fn ($row): string => '<a href="'.e(route('admin.lost_found.items.claims.index', (int) $row->id)).'">'.e(trans('lost_found::app.employee.claims.view_claims')).'</a>',
            ]);
        }

        if ($this->can($actor, 'lost_found.custody.manage')) {
            $this->addColumn([
                'index' => 'current_custodian_name',
                'label' => trans('lost_found::app.employee.items.current_custodian_name'),
                'type' => 'string',
            ]);
        }
    }

    protected function validatedRequest(): array
    {
        return request()->validate([
            'filters' => ['sometimes', 'array:all,status'],
            'filters.all' => ['sometimes', 'array', 'max:1'],
            'filters.all.*' => ['string', 'max:100'],
            'filters.status' => ['sometimes', 'array', 'max:5'],
            'filters.status.*' => ['string', Rule::in(array_column(ItemStatus::cases(), 'value'))],
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
            $this->queryBuilder->whereIn('lost_found_items.status', $requestedFilters['status']);
        }

        foreach ($requestedFilters['all'] ?? [] as $term) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
            $referencePattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], strtolower($term)).'%';

            $this->queryBuilder->where(function (Builder $query) use ($pattern, $referencePattern): void {
                $query->whereRaw("lost_found_items.public_reference_key LIKE ? ESCAPE '!'", [$referencePattern])
                    ->orWhereRaw("lost_found_items.title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("lost_found_items.found_location LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("lost_found_categories.code LIKE ? ESCAPE '!'", [$pattern]);
            });
        }

        return $this->queryBuilder;
    }

    protected function processRequestedSorting($requestedSort): Builder
    {
        return $this->queryBuilder->orderBy(
            self::SORT_COLUMNS[$requestedSort['column'] ?? 'id'],
            $requestedSort['order'] ?? 'desc',
        );
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

    private function actor(): User
    {
        return auth('user')->user();
    }

    private function can(User $actor, string $permission): bool
    {
        return $actor->role?->permission_type === 'all' || $actor->hasPermission($permission);
    }
}
