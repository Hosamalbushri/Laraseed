<?php

namespace Laraseed\Contacts\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Laraseed\Contacts\Admin\DataGrids\ContactDataGrid;
use Laraseed\Contacts\Http\Requests\StoreContactRequest;
use Laraseed\Contacts\Http\Requests\UpdateContactRequest;
use Laraseed\Contacts\Repositories\ContactRepository;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;

class ContactController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ContactRepository $contactRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(ContactDataGrid::class)->process();
        }

        return view('contacts_admin::index');
    }

    /**
     * Show the specified contact details.
     */
    public function show(int $id): View
    {
        $contact = $this->contactRepository->findOrFail($id);

        return view('contacts_admin::show', compact('contact'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('contacts_admin::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        try {
            $contact = $this->contactRepository->create($request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'first_name' => [$e->getMessage()],
            ]);
        }

        session()->flash('success', trans('contacts_admin::app.admin.create_success'));

        return redirect()->route('admin.contacts.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $contact = $this->contactRepository->findOrFail($id);

        return view('contacts_admin::edit', compact('contact'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, int $id): RedirectResponse
    {
        try {
            $contact = $this->contactRepository->update($request->validated(), $id);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'first_name' => [$e->getMessage()],
            ]);
        }

        session()->flash('success', trans('contacts_admin::app.admin.update_success'));

        return redirect()->route('admin.contacts.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $this->contactRepository->delete($id);

        $message = trans('contacts_admin::app.admin.delete_success');

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'message' => $message,
            ], 200);
        }

        session()->flash('success', $message);

        return redirect()->route('admin.contacts.index');
    }

    /**
     * Mass Delete the specified contacts.
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        $indices = $request->input('indices', []);
        $count = 0;

        foreach ($indices as $id) {
            $this->contactRepository->delete((int) $id);
            $count++;
        }

        return response()->json([
            'message' => trans('contacts_admin::app.admin.mass_delete_success', ['count' => $count]),
        ]);
    }

    /**
     * Mass Update status for the specified contacts.
     */
    public function massUpdate(MassUpdateRequest $request): JsonResponse
    {
        $indices = $request->input('indices', []);
        $status = (bool) $request->input('value');
        $count = 0;

        foreach ($indices as $id) {
            $this->contactRepository->update(['is_active' => $status], (int) $id);
            $count++;
        }

        return response()->json([
            'message' => trans('contacts_admin::app.admin.mass_update_success', ['count' => $count]),
        ]);
    }
}
