@auth('student')
    @php
        $student = auth('student')->user();
        $name = trim((string) $student->name);
        $initial = $name !== ''
            ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8')
            : '?';
        $profileImage = ! empty($student->profile_image)
            ? \Illuminate\Support\Facades\Storage::url($student->profile_image)
            : null;
    @endphp

    <details
        class="group relative js-student-account-details"
        data-role="student-account-dropdown"
    >
        <summary
            class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full bg-[color:var(--shop-primary)] text-sm font-semibold text-white shadow-sm marker:hidden [&::-webkit-details-marker]:hidden transition hover:bg-[color:var(--shop-primary-hover)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)] focus-visible:ring-offset-2"
            aria-label="{{ __('shop::app.layout.nav.student-menu') }}"
        >
            @if ($profileImage)
                <img src="{{ $profileImage }}" alt="{{ $student->name }}" class="h-10 w-10 rounded-full object-cover">
            @else
                {{ $initial }}
            @endif
        </summary>

        <div
            class="absolute end-0 top-full z-[10002] mt-2 min-w-[240px] overflow-hidden rounded-2xl border border-[color:var(--shop-border-soft)] bg-white shadow-lg"
        >
            <div class="border-b border-[color:var(--shop-border-soft)] bg-[color:var(--shop-surface)] p-4 text-center">
                <p class="text-sm font-semibold text-[color:var(--shop-text)]">
                    {{ $student->name }}
                </p>

                @if ($student->registration_number)
                    <p class="mt-1 text-xs text-[color:var(--shop-text-muted)]">
                        {{ __('shop::app.layout.nav.student-registration') }}:
                        {{ $student->registration_number }}
                    </p>
                @endif

                @if ($student->major)
                    <p class="mt-1 text-xs text-[color:var(--shop-text-muted)]">
                        {{ __('shop::app.student.account.fields.major') }}: {{ $student->major }}
                    </p>
                @endif
            </div>

            <div class="p-4">
                <div class="grid gap-2">
                    <a
                        href="{{ route('shop.student.account.edit') }}"
                        class="rounded-xl border border-[color:var(--shop-border-soft)] bg-white px-3 py-2 text-center text-sm font-semibold text-[color:var(--shop-text)] transition hover:bg-[color:var(--shop-surface)] hover:border-[color:var(--shop-border-hover)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)]"
                    >
                        {{ __('shop::app.layout.nav.student-account-edit') }}
                    </a>

                    <a
                        href="{{ route('shop.student.events.index') }}"
                        class="rounded-xl border border-[color:var(--shop-border-soft)] bg-white px-3 py-2 text-center text-sm font-semibold text-[color:var(--shop-text)] transition hover:bg-[color:var(--shop-surface)] hover:border-[color:var(--shop-border-hover)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)]"
                    >
                        {{ __('shop::app.layout.nav.student-events') }}
                    </a>
                </div>

                <form
                    method="post"
                    action="{{ route('student.logout') }}"
                    class="mt-3 pt-3 border-t border-[color:var(--shop-border-soft)]"
                >
                    @csrf
                    <button
                        type="submit"
                        class="block w-full rounded-xl bg-[color:var(--shop-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[color:var(--shop-primary-hover)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)]"
                    >
                        {{ __('shop::app.layout.nav.student-logout') }}
                    </button>
                </form>
            </div>
        </div>
    </details>

@pushOnce('scripts')
    <script>
        (() => {
            const selector = '[data-role="student-account-dropdown"]';

            const closeAll = (target) => {
                document.querySelectorAll(selector).forEach((details) => {
                    if (details.contains(target)) return;

                    // HTMLDetailsElement uses the "open" attribute.
                    if (details.hasAttribute('open')) {
                        details.removeAttribute('open');
                    }
                });
            };

            document.addEventListener('pointerdown', (event) => {
                const el = event.target;
                if (!el) return;

                closeAll(el);
            }, { passive: true });
        })();
    </script>
@endpushOnce
@endauth
