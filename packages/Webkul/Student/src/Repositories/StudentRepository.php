<?php

namespace Webkul\Student\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Student\Models\Student;

class StudentRepository extends Repository
{
    /**
     * Searchable fields.
     */
    protected $fieldSearchable = [
        'id',
        'name',
        'university_card_number',
        'registration_number',
        'major',
        'academic_level',
    ];

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Student::class;
    }
}
