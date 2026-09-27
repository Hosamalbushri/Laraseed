@props([
    'link' => [],
    'linkClass' => 'font-medium text-navyBlue hover:opacity-80',
])

@php
    $href = '#';
    if (! empty($link['route']) && \Illuminate\Support\Facades\Route::has($link['route'])) {
        $href = route($link['route']);
    } elseif (! empty($link['student_login'])) {
        $u = config('shop.student_login_url', '#');
        $href = ($u === '#' || $u === '') ? '#' : (\Illuminate\Support\Str::startsWith($u, ['http://', 'https://']) ? $u : url($u));
    } elseif (! empty($link['url'])) {
        $href = url($link['url']);
    }
    $isStudentLogin = ! empty($link['student_login']);
@endphp

<li>
    @if ($isStudentLogin && \Illuminate\Support\Facades\Route::has('student.logout') && auth('student'))
        <form
            method="post"
            action="{{ route('student.logout') }}"
            class="inline"
        >
            @csrf
            <button
                type="submit"
                class="{{ $linkClass }} cursor-pointer border-0 bg-transparent p-0 text-start"
            >
                {{ __('shop::app.components.layouts.footer.link-student-logout') }}
            </button>
        </form>
    @else
        <a
            href="{{ $href }}"
            class="{{ $linkClass }}"
        >
            {{ __($link['title']) }}
        </a>
    @endif
</li>
