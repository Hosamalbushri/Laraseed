<?php

namespace Webkul\Student\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Student\Models\Student;
use Webkul\Student\Repositories\StudentRepository;

class StudentAdminService
{
    public function __construct(
        protected StudentRepository $studentRepository
    ) {}

    public function createStudent(array $data, ?UploadedFile $profileImage = null): Student
    {
        return DB::transaction(function () use ($data, $profileImage) {
            if ($profileImage instanceof UploadedFile) {
                $data['profile_image'] = $profileImage->store('students', 'public');
            }

            return $this->studentRepository->create($data);
        });
    }

    public function updateStudent(int $id, array $data, ?UploadedFile $profileImage = null): Student
    {
        return DB::transaction(function () use ($id, $data, $profileImage) {
            $student = $this->studentRepository->findOrFail($id);

            if (empty($data['password'])) {
                unset($data['password'], $data['password_confirmation']);
            }

            if ($profileImage instanceof UploadedFile) {
                if ($student->profile_image) {
                    Storage::disk('public')->delete($student->profile_image);
                }

                $data['profile_image'] = $profileImage->store('students', 'public');
            }

            $this->studentRepository->update($data, $id);

            return $student->fresh();
        });
    }

    public function deleteStudent(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $student = $this->studentRepository->findOrFail($id);

            if ($student->profile_image) {
                Storage::disk('public')->delete($student->profile_image);
            }

            return (bool) $this->studentRepository->delete($id);
        });
    }

    public function massDeleteStudents(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            $deletedCount = 0;

            foreach ($ids as $id) {
                $student = $this->studentRepository->find($id);

                if (! $student) {
                    continue;
                }

                if ($student->profile_image) {
                    Storage::disk('public')->delete($student->profile_image);
                }

                $this->studentRepository->delete($id);
                $deletedCount++;
            }

            return $deletedCount;
        });
    }
}
