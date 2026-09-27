<?php

namespace Webkul\LostAndFound\Repositories;

use LogicException;
use Webkul\Core\Eloquent\Repository;
use Webkul\LostAndFound\Contracts\LostReport;

class LostReportRepository extends Repository
{
    public function model(): string
    {
        return LostReport::class;
    }

    public function create(array $data)
    {
        unset($data['resolved_found_item_id']);

        return parent::create($data);
    }

    public function update(array $data, $id, $attribute = 'id')
    {
        $protectedFields = [
            'status',
            'resolved_found_item_id',
            'student_id',
            'public_reference',
            'public_reference_key',
        ];

        if (array_intersect_key($data, array_flip($protectedFields))) {
            throw new LogicException('LostReport workflow and identity fields require a dedicated domain operation.');
        }

        return parent::update($data, $id, $attribute);
    }
}
