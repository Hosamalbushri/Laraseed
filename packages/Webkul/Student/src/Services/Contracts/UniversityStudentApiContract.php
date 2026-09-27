<?php

namespace Webkul\Student\Services\Contracts;

use Webkul\Student\DataTransferObjects\StudentProfileDto;
use Webkul\Student\Services\Exceptions\UniversityApiException;

interface UniversityStudentApiContract
{
    /**
     * Verify credentials with the university and return profile data on success.
     *
     * @throws UniversityApiException
     */
    public function verifyAndFetchProfile(string $universityCardNumber, string $password): StudentProfileDto;
}
