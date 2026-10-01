<?php

namespace Laraseed\Contacts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laraseed\Contacts\Http\Requests\StoreContactRequest;
use Laraseed\Contacts\Http\Requests\UpdateContactRequest;
use Laraseed\Contacts\Http\Resources\ContactResource;
use Laraseed\Contacts\Models\Contact;
use Laraseed\Contacts\Repositories\ContactRepository;

class ContactController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ContactRepository $contactRepository
    ) {}

    /**
     * Display a paginated listing of contacts.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        $type = $request->input('type');
        $isActive = $request->input('is_active');

        $contacts = $this->contactRepository->scopeQuery(function ($query) use ($type, $isActive) {
            if ($type !== null && in_array($type, [Contact::TYPE_PERSON, Contact::TYPE_ORGANIZATION], true)) {
                $query = $query->where('type', $type);
            }

            if ($isActive !== null) {
                $boolVal = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($boolVal !== null) {
                    $query = $query->where('is_active', $boolVal);
                }
            }

            return $query->orderBy('id', 'desc');
        })->paginate($perPage);

        return ContactResource::collection($contacts);
    }

    /**
     * Store a newly created contact.
     *
     * @throws ValidationException
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        try {
            $contact = $this->contactRepository->create($request->validated());

            return (new ContactResource($contact))
                ->response()
                ->setStatusCode(201);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'domain' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Display the specified contact.
     */
    public function show(int $id): ContactResource
    {
        $contact = $this->contactRepository->findOrFail($id);

        return new ContactResource($contact);
    }

    /**
     * Update the specified contact.
     *
     * @throws ValidationException
     */
    public function update(UpdateContactRequest $request, int $id): ContactResource
    {
        try {
            $contact = $this->contactRepository->update($request->validated(), $id);

            return new ContactResource($contact);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'domain' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(int $id): Response
    {
        $this->contactRepository->delete($id);

        return response()->noContent();
    }
}
