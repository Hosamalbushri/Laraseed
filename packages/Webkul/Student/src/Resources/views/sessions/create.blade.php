<x-shop::layouts
    :has-header="true"
    :has-feature="false"
    :has-footer="true"
    :title="core()->getConfigData('general.store.student_login.title') ?: core()->getConfigData('general.design.student_login.title') ?: __('student::app.login.title')"
>
    @php
        $cfg = fn (string $key): string => trim((string) (
            core()->getConfigData('general.store.student_login.'.$key)
            ?: core()->getConfigData('general.design.student_login.'.$key)
        ));

        $studentLoginCfg = [
            'title' => $cfg('title'),
            'description' => $cfg('description'),
            'eyebrow' => $cfg('eyebrow'),
            'panel_lead' => $cfg('panel_lead'),
            'card_number' => $cfg('card_number'),
            'password' => $cfg('password'),
            'remember' => $cfg('remember'),
            'submit' => $cfg('submit'),
            'back_portal' => $cfg('back_portal'),
        ];

        $t = static fn (string $key) => $studentLoginCfg[$key] !== '' ? $studentLoginCfg[$key] : __("student::app.login.$key");
        $loginLogoPath = $cfg('logo_image');
        $loginLogoUrl = $loginLogoPath !== '' ? \Illuminate\Support\Facades\Storage::url($loginLogoPath) : '';
        $loginPrimary = $cfg('primary_color') ?: '#8b5cf6';
        $loginAccent = $cfg('accent_color') ?: '#6366f1';
        $loginSurfaceStart = $cfg('surface_start') ?: '#f9fafb';
        $loginSurfaceEnd = $cfg('surface_end') ?: '#ffffff';
        $loginPanelStart = $cfg('panel_start') ?: '#8b5cf6';
        $loginPanelEnd = $cfg('panel_end') ?: '#6366f1';
    @endphp

    @pushOnce('styles', 'student-login-page-styles')
        <style>
            .student-login-page {
                --login-primary: {{ $loginPrimary }};
                --login-accent: {{ $loginAccent }};
                --login-surface-start: {{ $loginSurfaceStart }};
                --login-surface-end: {{ $loginSurfaceEnd }};
                --login-panel-start: {{ $loginPanelStart }};
                --login-panel-end: {{ $loginPanelEnd }};
                font-family: 'Cairo', 'Inter', sans-serif;
                background: linear-gradient(135deg, var(--login-surface-start) 0%, var(--login-surface-end) 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                position: relative;
                overflow: hidden;
            }
            .student-login-bg {
                position: absolute;
                inset: 0;
                overflow: hidden;
                z-index: 0;
            }
            .student-login-bg span {
                position: absolute;
                display: block;
                width: 20px;
                height: 20px;
                background: color-mix(in srgb, var(--login-primary) 18%, white);
                bottom: -150px;
                animation: studentLoginFloat 15s linear infinite;
                border-radius: 50%;
                opacity: 0.45;
            }
            @keyframes studentLoginFloat {
                0% { transform: translateY(0) rotate(0deg); opacity: 1; }
                100% { transform: translateY(-1200px) rotate(720deg); opacity: 0; }
            }
            .student-login-wrapper {
                position: relative;
                z-index: 10;
                width: 100%;
                max-width: 1200px;
                background: white;
                border-radius: 32px;
                overflow: hidden;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                display: flex;
                flex-direction: row;
                min-height: 600px;
            }
            .student-login-brand {
                flex: 1;
                background: linear-gradient(135deg, var(--login-panel-start), var(--login-panel-end));
                padding: 60px 40px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                position: relative;
                overflow: hidden;
                color: white;
            }
            .student-login-brand-orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(60px);
                opacity: 0.3;
                animation: studentLoginOrb 15s ease-in-out infinite;
            }
            .student-login-brand-orb-1 {
                width: 300px; height: 300px;
                background: radial-gradient(circle, rgba(255,255,255,0.8), rgba(255,255,255,0.3));
                top: -100px; right: -100px;
            }
            .student-login-brand-orb-2 {
                width: 250px; height: 250px;
                background: radial-gradient(circle, rgba(255,255,255,0.6), rgba(255,255,255,0.2));
                bottom: -80px; left: -80px; animation-delay: -5s;
            }
            .student-login-brand-orb-3 {
                width: 200px; height: 200px;
                background: radial-gradient(circle, rgba(255,255,255,0.5), rgba(255,255,255,0.2));
                top: 50%; left: 50%; transform: translate(-50%, -50%); animation-delay: -10s;
            }
            @keyframes studentLoginOrb {
                0%, 100% { transform: translate(0, 0) scale(1); }
                33% { transform: translate(20px, -20px) scale(1.1); }
                66% { transform: translate(-15px, 15px) scale(0.9); }
            }
            .student-login-form {
                flex: 1;
                padding: 60px 50px;
                background: white;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .student-login-brand-logo {
                max-height: 120px;
                width: auto;
                max-width: min(320px, 95%);
                object-fit: contain;
                margin: 0 auto 22px;
            }
            .student-login-h2 { font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
            .student-login-p { color: #6b7280; font-size: 14px; }
            .student-login-label { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 14px; font-weight: 600; color: #374151; }
            .student-login-label i { color: var(--login-primary); font-size: 14px; }
            .student-login-input-wrap { position: relative; display: flex; align-items: center; }
            .student-login-icon { position: absolute; right: 16px; color: #9ca3af; font-size: 16px; pointer-events: none; }
            .student-login-input {
                width: 100%;
                padding: 14px 48px 14px 16px;
                border: 1.5px solid #e5e7eb;
                border-radius: 14px;
                font-size: 15px;
                color: #1f2937;
                transition: all 0.3s ease;
                background: #f9fafb;
            }
            .student-login-input:focus {
                outline: none;
                border-color: var(--login-primary);
                background: white;
                box-shadow: 0 0 0 4px color-mix(in srgb, var(--login-primary) 14%, white);
            }
            .student-login-toggle {
                position: absolute;
                left: 16px;
                border: 0;
                background: transparent;
                color: #9ca3af;
                cursor: pointer;
                font-size: 16px;
            }
            .student-login-submit {
                width: 100%;
                background: linear-gradient(135deg, var(--login-primary), var(--login-accent));
                border: none;
                padding: 14px;
                border-radius: 14px;
                color: white;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 4px;
            }
            .student-login-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 25px color-mix(in srgb, var(--login-primary) 30%, transparent); }
            @media (max-width: 968px) {
                .student-login-wrapper { flex-direction: column; max-width: 500px; }
                .student-login-brand { padding: 40px 30px; min-height: 320px; }
                .student-login-form { padding: 40px 30px; }
            }
            @media (max-width: 480px) {
                .student-login-form { padding: 30px 24px; }
            }
        </style>
    @endPushOnce

    <section class="student-login-page">
        <div class="student-login-bg" aria-hidden="true">
            @for ($i = 0; $i < 28; $i++)
                <span style="
                    left: {{ mt_rand(0, 100) }}%;
                    width: {{ mt_rand(10, 44) }}px;
                    height: {{ mt_rand(10, 44) }}px;
                    animation-delay: -{{ mt_rand(0, 15) }}s;
                    animation-duration: {{ mt_rand(10, 24) }}s;
                "></span>
            @endfor
        </div>

        <div class="student-login-wrapper">
            <aside class="student-login-brand">
                <div class="student-login-brand-orb student-login-brand-orb-1"></div>
                <div class="student-login-brand-orb student-login-brand-orb-2"></div>
                <div class="student-login-brand-orb student-login-brand-orb-3"></div>

                <div class="relative z-10 text-center">
                    @if ($loginLogoUrl !== '')
                        <img
                            src="{{ $loginLogoUrl }}"
                            alt="{{ $t('title') }}"
                            class="student-login-brand-logo"
                        >
                    @endif
                    <p class="mx-auto max-w-xs text-sm leading-relaxed text-white/90">
                        {{ $t('panel_lead') }}
                    </p>
                </div>
            </aside>

            <div class="student-login-form">
                <div class="mb-8 text-right">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-[0.14em]" style="color: var(--login-primary)">
                        {{ $t('eyebrow') }}
                    </p>
                    <h1 id="student-login-heading" class="student-login-h2">{{ $t('title') }}</h1>
                    <p class="student-login-p">{{ $t('description') }}</p>
                </div>

                <form method="post" action="{{ route('student.login.store') }}" novalidate>
                    @csrf

                    <div class="mb-6">
                        <label for="university_card_number" class="student-login-label">
                            <i class="fas fa-id-card" aria-hidden="true"></i>
                            <span>{{ $t('card_number') }}</span>
                        </label>
                        <div class="student-login-input-wrap">
                            <i class="fas fa-user-graduate student-login-icon" aria-hidden="true"></i>
                            <input
                                id="university_card_number"
                                type="text"
                                name="university_card_number"
                                value="{{ old('university_card_number') }}"
                                class="student-login-input"
                                placeholder="{{ $t('card_number') }}"
                                autocomplete="username"
                                required
                            >
                        </div>
                        @error('university_card_number')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="password" class="student-login-label">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <span>{{ $t('password') }}</span>
                        </label>
                        <div class="student-login-input-wrap">
                            <i class="fas fa-key student-login-icon" aria-hidden="true"></i>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="student-login-input"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                id="student-login-toggle-password"
                                class="student-login-toggle"
                                aria-controls="password"
                                aria-pressed="false"
                                data-label-show="{{ __('student::app.login.show_password') }}"
                                data-label-hide="{{ __('student::app.login.hide_password') }}"
                                aria-label="{{ __('student::app.login.show_password') }}"
                            >
                                <i class="far fa-eye" id="student-login-toggle-password-icon" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6 flex items-center justify-between gap-3">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-zinc-600">
                            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded" @checked(old('remember'))>
                            <span>{{ $t('remember') }}</span>
                        </label>
                    </div>

                    <button type="submit" class="student-login-submit">
                        {{ $t('submit') }}
                    </button>
                </form>

                @if (\Illuminate\Support\Facades\Route::has('shop.home.index'))
                    <p class="mt-6 text-center text-sm text-zinc-500">
                        <a
                            href="{{ route('shop.home.index') }}"
                            class="font-semibold underline-offset-4 hover:underline"
                            style="color: var(--login-primary)"
                        >
                            {{ $t('back_portal') }}
                        </a>
                    </p>
                @endif
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            (function () {
                var btn = document.getElementById('student-login-toggle-password');
                var input = document.getElementById('password');
                var icon = document.getElementById('student-login-toggle-password-icon');
                if (!btn || !input || !icon) return;
                btn.addEventListener('click', function () {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                    btn.setAttribute('aria-label', show ? btn.getAttribute('data-label-hide') : btn.getAttribute('data-label-show'));
                    icon.className = show ? 'far fa-eye-slash' : 'far fa-eye';
                });
            })();
        </script>
    @endpush
</x-shop::layouts>
