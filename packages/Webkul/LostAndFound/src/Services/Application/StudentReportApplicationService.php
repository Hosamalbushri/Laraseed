<?php

namespace Webkul\LostAndFound\Services\Application;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Webkul\LostAndFound\Enums\ReportStatus;
use Webkul\LostAndFound\Models\LostReport;
use Webkul\LostAndFound\Models\LostReportImage;
use Webkul\LostAndFound\Repositories\LostReportRepository;
use Webkul\LostAndFound\Services\LostReportImageService;
use Webkul\Student\Models\Student;

class StudentReportApplicationService
{
    public function __construct(
        protected LostReportRepository $reportRepository,
        protected LostReportImageService $imageService
    ) {}

    public function createLostReport(Student $actor, array $data): LostReport
    {
        $data['student_id'] = $actor->id;
        $data['status'] = ReportStatus::DRAFT->value;
        unset($data['resolved_found_item_id']);

        if (empty($data['public_reference'])) {
            $data['public_reference'] = 'LR-'.strtoupper(Str::random(10));
        }

        return $this->reportRepository->create($data);
    }

    public function updateOwnLostReport(Student $actor, LostReport $report, array $data): LostReport
    {
        LostAndFoundAuthorization::authorizeStudentOwnership($actor, $report->student_id, 'LostReport');

        return $this->reportRepository->update($data, $report->id);
    }

    public function addOwnLostReportImage(
        Student $actor,
        LostReport $report,
        UploadedFile $file
    ): LostReportImage {
        LostAndFoundAuthorization::authorizeStudentOwnership($actor, $report->student_id, 'LostReport');

        return $this->imageService->addImage($report, $file);
    }
}
