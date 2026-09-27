<?php

namespace Webkul\LostAndFound\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Http\Requests\Employee\StoreFoundItemRequest;
use Webkul\LostAndFound\Http\Requests\Employee\UpdateFoundItemRequest;
use Webkul\LostAndFound\Http\Requests\Employee\UploadFoundItemImageRequest;
use Webkul\LostAndFound\Services\Application\EmployeeItemApplicationService;

class EmployeeFoundItemController extends Controller
{
    public function __construct(
        protected EmployeeItemApplicationService $applicationService
    ) {}

    public function store(StoreFoundItemRequest $request): JsonResponse
    {
        $actor = auth('user')->user();

        $item = $this->applicationService->createFoundItem($actor, $request->validated());

        return response()->json([
            'message' => trans('lost_found::app.admin.items.created_success'),
            'data' => [
                'id' => $item->id,
                'public_reference' => $item->public_reference,
                'title' => $item->title,
                'status' => $item->status->value,
                'category_id' => $item->category_id,
                'logged_by_user_id' => $item->logged_by_user_id,
            ],
        ], 201);
    }

    public function update(UpdateFoundItemRequest $request, int $id): JsonResponse
    {
        $actor = auth('user')->user();

        $item = $this->applicationService->updateFoundItem($actor, $id, $request->validated());

        return response()->json([
            'message' => trans('lost_found::app.admin.items.updated_success'),
            'data' => [
                'id' => $item->id,
                'public_reference' => $item->public_reference,
                'title' => $item->title,
                'status' => $item->status->value,
                'category_id' => $item->category_id,
            ],
        ], 200);
    }

    public function uploadImage(UploadFoundItemImageRequest $request, int $id): JsonResponse
    {
        $actor = auth('user')->user();
        $visibility = FoundItemImageVisibility::from($request->validated('visibility'));

        $image = $this->applicationService->addFoundItemImage(
            $actor,
            $id,
            $visibility,
            $request->file('image')
        );

        return response()->json([
            'message' => trans('lost_found::app.admin.items.image_uploaded_success'),
            'data' => [
                'id' => $image->id,
                'found_item_id' => $image->found_item_id,
                'visibility' => $image->visibility->value,
                'created_by_user_id' => $image->created_by_user_id,
                'mime_type' => $image->mime_type,
                'byte_size' => $image->byte_size,
            ],
        ], 201);
    }
}
