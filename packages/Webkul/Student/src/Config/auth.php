<?php

use Webkul\Student\Models\Student;

return [
    'guards' => [
        'student' => [
            'driver' => 'session',
            'provider' => 'students',
        ],
    ],

    'providers' => [
        'students' => [
            'driver' => 'eloquent',
            'model' => Student::class,
        ],
    ],
];
