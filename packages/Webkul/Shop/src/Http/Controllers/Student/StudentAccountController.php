<?php

namespace Webkul\Shop\Http\Controllers\Student;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentAccountController extends Controller
{
    public function edit(): View
    {
        $student = Auth::guard('student')->user();

        return view('shop::student.account.edit', [
            'student' => $student,
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $student = Auth::guard('student')->user();

        $data = $request->validate([
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'required_with:current_password', 'string', 'min:6', 'confirmed'],
        ]);

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('students/profile', 'public');
            $student->profile_image = $path;
        }

        if (! empty($data['new_password'])) {
            if (! Hash::check((string) $data['current_password'], (string) $student->password)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => __('shop::app.student.account.password.current-invalid'),
                        'errors' => [
                            'current_password' => [__('shop::app.student.account.password.current-invalid')],
                        ],
                    ], 422);
                }

                return back()
                    ->withErrors(['current_password' => __('shop::app.student.account.password.current-invalid')])
                    ->withInput();
            }

            $student->password = $data['new_password'];
        }

        $student->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('shop::app.student.account.update-success'),
                'profile_image_url' => $student->profile_image
                    ? Storage::url($student->profile_image)
                    : null,
            ]);
        }

        return redirect()
            ->route('shop.student.account.edit')
            ->with('success', __('shop::app.student.account.update-success'));
    }
}
