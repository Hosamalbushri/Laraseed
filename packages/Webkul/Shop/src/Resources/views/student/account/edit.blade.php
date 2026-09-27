@php
    $initialAvatarUrl = ! empty($student->profile_image)
        ? \Illuminate\Support\Facades\Storage::url($student->profile_image)
        : null;
@endphp

<x-shop::layouts :title="__('shop::app.student.account.edit.title')">
    <div class="container px-4 py-10 max-md:px-4 lg:px-[60px]">
        <div class="mb-8 text-center md:text-start">
            <h1 class="text-3xl font-bold tracking-tight text-[color:var(--shop-text)] sm:text-4xl">
                {{ __('shop::app.student.account.edit.heading') }}
            </h1>
            <p class="mt-2 text-[color:var(--shop-text-muted)]">
                {{ __('shop::app.student.account.edit.subheading') }}
            </p>
        </div>

        <form
            id="student-account-form"
            method="POST"
            action="{{ route('shop.student.account.update') }}"
            enctype="multipart/form-data"
            class="mx-auto max-w-3xl rounded-2xl border border-[color:var(--shop-border-soft)] bg-white p-6 shadow-sm md:p-8"
        >
            @csrf

            {{-- Profile image: centered circle, tap to choose (hidden file input) --}}
            <div class="mb-8 flex flex-col items-center">
                <input
                    id="student-profile-image-input"
                    type="file"
                    name="profile_image"
                    accept="image/*"
                    class="sr-only"
                    tabindex="-1"
                    aria-hidden="true"
                >

                <label
                    for="student-profile-image-input"
                    class="group relative flex h-36 w-36 cursor-pointer select-none rounded-full focus-within:outline-none focus-within:ring-2 focus-within:ring-[color:var(--shop-ring)] focus-within:ring-offset-2"
                >
                    <span class="sr-only">{{ __('shop::app.student.account.edit.avatar-label') }}</span>

                    <span
                        class="relative flex h-full w-full overflow-hidden rounded-full border-2 border-[color:var(--shop-border-soft)] bg-[color:var(--shop-surface)] shadow-inner ring-2 ring-[color:var(--shop-surface-strong)]"
                    >
                        <img
                            id="student-avatar-preview"
                            src="{{ $initialAvatarUrl ?: '' }}"
                            alt="{{ $initialAvatarUrl ? $student->name : '' }}"
                            class="absolute inset-0 h-full w-full object-cover transition-opacity @if (! $initialAvatarUrl) opacity-0 @endif"
                            width="144"
                            height="144"
                        >

                        <span
                            id="student-avatar-fallback"
                            class="absolute inset-0 flex items-center justify-center text-[color:var(--shop-primary)] transition-opacity @if ($initialAvatarUrl) opacity-0 @endif"
                            aria-hidden="true"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                class="h-20 w-20 opacity-90"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </span>

                        <span
                            class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-full bg-black/35 opacity-60 transition-opacity group-hover:opacity-100 md:opacity-0 md:group-hover:opacity-100"
                            aria-hidden="true"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                class="h-9 w-9 text-white drop-shadow"
                            >
                                <path d="M12 9a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5ZM4.5 17.25V9.75A2.25 2.25 0 0 1 6.75 7.5h1.94l.43-1.29A2.25 2.25 0 0 1 10.64 4.5h2.72a2.25 2.25 0 0 1 2.13 1.71l.43 1.29h1.93A2.25 2.25 0 0 1 19.5 9.75v7.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25Z" />
                            </svg>
                        </span>
                    </span>
                </label>

                <p class="mt-3 max-w-xs text-center text-sm text-[color:var(--shop-text-muted)]">
                    {{ __('shop::app.student.account.edit.avatar-hint') }}
                </p>
                <p
                    id="student-account-error-profile_image"
                    class="mt-2 hidden text-center text-sm font-medium text-red-600"
                    role="alert"
                ></p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-1">
                    <x-shop::form.label for="university_card_number">
                        {{ __('shop::app.student.account.fields.university-card-number') }}
                    </x-shop::form.label>
                    <x-shop::form.input
                        id="university_card_number"
                        name="university_card_number"
                        :value="$student->university_card_number ?? ''"
                        disabled
                    />
                </div>

                <div class="md:col-span-1">
                    <x-shop::form.label for="name">
                        {{ __('shop::app.student.account.fields.name') }}
                    </x-shop::form.label>
                    <x-shop::form.input
                        id="name"
                        name="name"
                        :value="$student->name ?? ''"
                        disabled
                    />
                </div>

                <div class="md:col-span-1">
                    <x-shop::form.label for="major">
                        {{ __('shop::app.student.account.fields.major') }}
                    </x-shop::form.label>
                    <x-shop::form.input
                        id="major"
                        name="major"
                        :value="$student->major ?? ''"
                        disabled
                    />
                </div>
            </div>

            <div class="mt-8 rounded-2xl border border-[color:var(--shop-border-soft)] bg-[color:var(--shop-surface)]/60 p-5">
                <h2 class="text-sm font-semibold text-[color:var(--shop-text)]">
                    {{ __('shop::app.student.account.sections.password') }}
                </h2>

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <div>
                        <x-shop::form.label for="current_password">
                            {{ __('shop::app.student.account.password.current') }}
                        </x-shop::form.label>
                        <x-shop::form.input id="current_password" name="current_password" type="password" autocomplete="current-password" />
                        <p
                            id="student-account-error-current_password"
                            class="mt-1 hidden text-sm font-medium text-red-600"
                            role="alert"
                        ></p>
                    </div>

                    <div>
                        <x-shop::form.label for="new_password">
                            {{ __('shop::app.student.account.password.new') }}
                        </x-shop::form.label>
                        <x-shop::form.input id="new_password" name="new_password" type="password" autocomplete="new-password" />
                        <p
                            id="student-account-error-new_password"
                            class="mt-1 hidden text-sm font-medium text-red-600"
                            role="alert"
                        ></p>
                    </div>

                    <div>
                        <x-shop::form.label for="new_password_confirmation">
                            {{ __('shop::app.student.account.password.confirm') }}
                        </x-shop::form.label>
                        <x-shop::form.input id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password" />
                        <p
                            id="student-account-error-new_password_confirmation"
                            class="mt-1 hidden text-sm font-medium text-red-600"
                            role="alert"
                        ></p>
                    </div>
                </div>
            </div>

            <p
                id="student-account-form-error-global"
                class="mt-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
                role="alert"
            ></p>

            <p
                id="student-account-form-success"
                class="mt-4 hidden rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
                role="status"
            ></p>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3 md:justify-start">
                <button
                    id="student-account-save"
                    type="submit"
                    class="inline-flex min-w-[140px] items-center justify-center rounded-lg bg-[color:var(--shop-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[color:var(--shop-primary-hover)] disabled:pointer-events-none disabled:opacity-60"
                >
                    <span id="student-account-save-label">{{ __('shop::app.student.account.edit.save') }}</span>
                    <span id="student-account-save-loading" class="hidden">{{ __('shop::app.student.account.edit.saving') }}</span>
                </button>

                <a
                    href="{{ route('shop.student.events.index') }}"
                    class="rounded-lg border border-[color:var(--shop-border-soft)] px-5 py-2.5 text-sm font-semibold text-[color:var(--shop-text)] transition hover:bg-[color:var(--shop-surface)]"
                >
                    {{ __('shop::app.student.events.title') }}
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            (function () {
                var form = document.getElementById('student-account-form');
                if (!form) return;

                var fileInput = document.getElementById('student-profile-image-input');
                var preview = document.getElementById('student-avatar-preview');
                var fallback = document.getElementById('student-avatar-fallback');
                var saveBtn = document.getElementById('student-account-save');
                var saveLabel = document.getElementById('student-account-save-label');
                var saveLoading = document.getElementById('student-account-save-loading');
                var previewObjectUrl = null;

                function setPreviewFromFile(file) {
                    if (previewObjectUrl) {
                        URL.revokeObjectURL(previewObjectUrl);
                        previewObjectUrl = null;
                    }
                    if (!file || !file.type || file.type.indexOf('image/') !== 0) return;
                    previewObjectUrl = URL.createObjectURL(file);
                    preview.src = previewObjectUrl;
                    preview.classList.remove('opacity-0');
                    if (fallback) fallback.classList.add('opacity-0');
                }

                function setPreviewFromServerUrl(url) {
                    if (previewObjectUrl) {
                        URL.revokeObjectURL(previewObjectUrl);
                        previewObjectUrl = null;
                    }
                    if (url) {
                        preview.src = url;
                        preview.classList.remove('opacity-0');
                        if (fallback) fallback.classList.add('opacity-0');
                    } else {
                        preview.src = '';
                        preview.classList.add('opacity-0');
                        if (fallback) fallback.classList.remove('opacity-0');
                    }
                }

                if (fileInput) {
                    fileInput.addEventListener('change', function () {
                        var f = fileInput.files && fileInput.files[0];
                        setPreviewFromFile(f || null);
                        clearFieldError('profile_image');
                    });
                }

                function clearFieldError(field) {
                    var el = document.getElementById('student-account-error-' + field);
                    if (el) {
                        el.textContent = '';
                        el.classList.add('hidden');
                    }
                }

                function clearAllFieldErrors() {
                    ['profile_image', 'current_password', 'new_password', 'new_password_confirmation'].forEach(clearFieldError);
                    var g = document.getElementById('student-account-form-error-global');
                    if (g) {
                        g.textContent = '';
                        g.classList.add('hidden');
                    }
                    var s = document.getElementById('student-account-form-success');
                    if (s) {
                        s.textContent = '';
                        s.classList.add('hidden');
                    }
                }

                function showFieldError(field, message) {
                    var el = document.getElementById('student-account-error-' + field);
                    if (el) {
                        el.textContent = message;
                        el.classList.remove('hidden');
                    }
                }

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    clearAllFieldErrors();

                    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    var token = tokenMeta ? tokenMeta.getAttribute('content') : '';

                    var fd = new FormData(form);

                    saveBtn.disabled = true;
                    saveLabel.classList.add('hidden');
                    saveLoading.classList.remove('hidden');

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: fd,
                        credentials: 'same-origin',
                    })
                        .then(function (res) {
                            return res.text().then(function (text) {
                                var data = {};
                                try {
                                    data = text ? JSON.parse(text) : {};
                                } catch (e) {
                                    data = { message: text };
                                }
                                return { ok: res.ok, status: res.status, data: data };
                            });
                        })
                        .then(function (result) {
                            if (result.ok) {
                                var msg = (result.data && result.data.message) || '';
                                var successEl = document.getElementById('student-account-form-success');
                                if (successEl && msg) {
                                    successEl.textContent = msg;
                                    successEl.classList.remove('hidden');
                                }
                                if (result.data && result.data.profile_image_url) {
                                    setPreviewFromServerUrl(result.data.profile_image_url);
                                }
                                var cp = document.getElementById('current_password');
                                var np = document.getElementById('new_password');
                                var npc = document.getElementById('new_password_confirmation');
                                if (cp) cp.value = '';
                                if (np) np.value = '';
                                if (npc) npc.value = '';
                                if (fileInput) fileInput.value = '';
                                return;
                            }

                            var d = result.data || {};
                            var fieldKeys = [
                                'profile_image',
                                'current_password',
                                'new_password',
                                'new_password_confirmation',
                            ];
                            if (d.errors && typeof d.errors === 'object') {
                                Object.keys(d.errors).forEach(function (key) {
                                    var arr = d.errors[key];
                                    var text = Array.isArray(arr) ? arr[0] : String(arr);
                                    if (fieldKeys.indexOf(key) !== -1) {
                                        showFieldError(key, text);
                                    }
                                });
                            }
                            var global = document.getElementById('student-account-form-error-global');
                            if (global) {
                                var shownField = fieldKeys.some(function (key) {
                                    var el = document.getElementById('student-account-error-' + key);
                                    return el && !el.classList.contains('hidden');
                                });
                                if (!shownField) {
                                    global.textContent =
                                        (d.message && String(d.message)) ||
                                        @json(__('shop::app.student.account.edit.ajax-error'));
                                    global.classList.remove('hidden');
                                }
                            }
                        })
                        .catch(function () {
                            var global = document.getElementById('student-account-form-error-global');
                            if (global) {
                                global.textContent = @json(__('shop::app.student.account.edit.ajax-error'));
                                global.classList.remove('hidden');
                            }
                        })
                        .finally(function () {
                            saveBtn.disabled = false;
                            saveLabel.classList.remove('hidden');
                            saveLoading.classList.add('hidden');
                        });
                });
            })();
        </script>
    @endpush
</x-shop::layouts>
