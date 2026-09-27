<?php

namespace Webkul\Student\DataTransferObjects;

readonly class StudentProfileDto
{
    public function __construct(
        public string $name,
        public ?string $registrationNumber = null,
        public ?string $major = null,
        public ?string $academicLevel = null,
    ) {}
}
