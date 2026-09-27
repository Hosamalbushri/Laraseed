<?php

namespace Webkul\Student\Services;

use Webkul\Student\DataTransferObjects\StudentProfileDto;
use Webkul\Student\Services\Contracts\UniversityStudentApiContract;
use Webkul\Student\Services\Exceptions\UniversityApiException;

class FakeUniversityStudentApiClient implements UniversityStudentApiContract
{
    public function verifyAndFetchProfile(string $universityCardNumber, string $password): StudentProfileDto
    {
        if (strlen($password) < 4) {
            throw new UniversityApiException(__('student::app.university.invalid_credentials'));
        }

        return new StudentProfileDto(
            name: 'Demo Student ('.$universityCardNumber.')',
            registrationNumber: $universityCardNumber,
            major: 'Demo Major',
            academicLevel: '1',
        );
    }
}
