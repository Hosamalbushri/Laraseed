<?php

namespace Webkul\Student\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Webkul\Student\Http\Requests\StudentLoginRequest;
use Webkul\Student\Models\Student;
use Webkul\Student\Services\Contracts\UniversityStudentApiContract;
use Webkul\Student\Services\Exceptions\UniversityApiException;

class StudentSessionController extends Controller
{
    /**
     * Show the student login / first-time registration form.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('student')->check()) {
            return redirect()->to(config('student.redirect_after_login', '/'));
        }

        $this->captureIntendedRedirectFromQuery();

        return view('student::sessions.create');
    }

    /**
     * Allow ?intended= URL so subscribe → login → return to event works.
     */
    protected function captureIntendedRedirectFromQuery(): void
    {
        $raw = request()->query('intended');
        if (! is_string($raw) || $raw === '') {
            return;
        }

        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            $root = rtrim((string) config('app.url'), '/');
            if (str_starts_with($raw, $root)) {
                session(['url.intended' => $raw]);
            }

            return;
        }

        if (str_starts_with($raw, '/') && ! str_starts_with($raw, '//')) {
            session(['url.intended' => url($raw)]);
        }
    }

    /**
     * Verify with university API (new students) or local password (returning), then start session.
     */
    public function store(StudentLoginRequest $request, UniversityStudentApiContract $universityApi): RedirectResponse
    {
        $card = $request->validated('university_card_number');
        $password = $request->validated('password');
        $remember = (bool) $request->boolean('remember');

        $existing = Student::query()->where('university_card_number', $card)->first();

        if ($existing) {
            if (! Auth::guard('student')->attempt([
                'university_card_number' => $card,
                'password' => $password,
            ], $remember)) {
                return back()
                    ->withInput($request->only('university_card_number'))
                    ->withErrors(['university_card_number' => __('student::app.login.failed')])
                    ->with('error', __('student::app.login.failed'));
            }

            $request->session()->regenerate();

            return redirect()->intended(config('student.redirect_after_login', '/'))
                ->with('success', __('student::app.login.welcome_back'));
        }

        try {
            $profile = $universityApi->verifyAndFetchProfile($card, $password);
        } catch (UniversityApiException $e) {
            return back()
                ->withInput($request->only('university_card_number'))
                ->withErrors(['university_card_number' => $e->getMessage()])
                ->with('error', $e->getMessage());
        }

        $student = Student::query()->create([
            'university_card_number' => $card,
            'password' => $password,
            'name' => $profile->name,
            'registration_number' => $profile->registrationNumber,
            'major' => $profile->major,
            'academic_level' => $profile->academicLevel,
        ]);

        Auth::guard('student')->login($student, $remember);
        $request->session()->regenerate();

        return redirect()->intended(config('student.redirect_after_login', '/'))
            ->with('success', __('student::app.login.registered'));
    }

    /**
     * Log the student out.
     */
    public function destroy(): RedirectResponse
    {
        Auth::guard('student')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('student.login')
            ->with('success', __('student::app.login.logged_out'));
    }
}
