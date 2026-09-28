<?php

namespace Webkul\LostAndFound\Http\Controllers\Employee;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Webkul\LostAndFound\Http\Requests\Employee\CompleteHandoverRequest;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Services\Application\EmployeeHandoverApplicationService;

class EmployeeHandoverController extends Controller
{
    public function __construct(
        protected EmployeeHandoverApplicationService $applicationService
    ) {}

    public function complete(CompleteHandoverRequest $request, int $id): JsonResponse
    {
        $actor = auth('user')->user();
        $item = FoundItem::findOrFail($id);

        try {
            $handover = $this->applicationService->completeHandover(
                $actor,
                $item,
                $request->validated(),
            );
        } catch (DomainException|InvalidArgumentException) {
            return response()->json([], 422);
        }

        return response()->json([
            'data' => [
                'id' => $handover->getKey(),
                'found_item_id' => $handover->found_item_id,
                'item_status' => $handover->foundItem()->value('status'),
                'handed_over_at' => $handover->handed_over_at->toIso8601String(),
            ],
        ]);
    }
}
