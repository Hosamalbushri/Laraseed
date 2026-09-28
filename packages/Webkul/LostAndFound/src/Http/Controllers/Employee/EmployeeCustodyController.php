<?php

namespace Webkul\LostAndFound\Http\Controllers\Employee;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Webkul\LostAndFound\Http\Requests\Employee\MoveStorageRequest;
use Webkul\LostAndFound\Http\Requests\Employee\ReceiveCustodyRequest;
use Webkul\LostAndFound\Http\Requests\Employee\TransferCustodyRequest;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Services\Application\EmployeeCustodyApplicationService;
use Webkul\User\Models\User;

class EmployeeCustodyController extends Controller
{
    public function __construct(
        protected EmployeeCustodyApplicationService $applicationService
    ) {}

    public function receive(ReceiveCustodyRequest $request, int $id): JsonResponse
    {
        $item = FoundItem::findOrFail($id);
        $actor = auth('user')->user();

        try {
            $updatedItem = $this->applicationService->receiveItem(
                $actor,
                $item,
                $request->validated('storage_location'),
                $request->validated('notes')
            );
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->transformItem($updatedItem),
            'message' => trans('lost_found::app.admin.custody.received_success'),
        ]);
    }

    public function transfer(TransferCustodyRequest $request, int $id): JsonResponse
    {
        $item = FoundItem::findOrFail($id);
        $actor = auth('user')->user();

        $newCustodian = User::findOrFail($request->validated('to_custodian_user_id'));

        try {
            $updatedItem = $this->applicationService->transferItem(
                $actor,
                $item,
                $newCustodian,
                $request->validated('to_storage_location'),
                $request->validated('notes'),
                $request->validated('expected_custodian_user_id'),
                $request->validated('expected_storage_location')
            );
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->transformItem($updatedItem),
            'message' => trans('lost_found::app.admin.custody.transferred_success'),
        ]);
    }

    public function moveStorage(MoveStorageRequest $request, int $id): JsonResponse
    {
        $item = FoundItem::findOrFail($id);
        $actor = auth('user')->user();

        try {
            $updatedItem = $this->applicationService->moveStorage(
                $actor,
                $item,
                $request->validated('to_storage_location'),
                $request->validated('notes'),
                $request->validated('expected_custodian_user_id'),
                $request->validated('expected_storage_location')
            );
        } catch (DomainException|InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->transformItem($updatedItem),
            'message' => trans('lost_found::app.admin.custody.moved_success'),
        ]);
    }

    protected function transformItem(FoundItem $item): array
    {
        return [
            'id' => $item->id,
            'public_reference' => $item->public_reference,
            'status' => $item->status->value,
            'current_custodian_user_id' => $item->current_custodian_user_id,
            'current_storage_location' => $item->current_storage_location,
            'custody_started_at' => $item->custody_started_at?->toIso8601String(),
            'custody_changed_at' => $item->custody_changed_at?->toIso8601String(),
        ];
    }
}
