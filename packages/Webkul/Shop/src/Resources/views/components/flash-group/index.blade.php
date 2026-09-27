{{--
    Session flash toasts (Bagisto-style placement and colors, no Vue).
    Types: success, warning, error, info — same as session()->flash('success', ...).
--}}
@php
    $shopFlashes = [];
    foreach (['success', 'warning', 'error', 'info'] as $type) {
        if (session()->has($type)) {
            $shopFlashes[] = [
                'type' => $type,
                'message' => session($type),
            ];
        }
    }
@endphp

@if (count($shopFlashes) > 0)
    @push('scripts')
        <script>
            (function () {
                document.querySelectorAll('[data-shop-flash-dismiss]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var root = btn.closest('[data-shop-flash]');
                        if (root) {
                            root.remove();
                        }
                    });
                });
                document.querySelectorAll('[data-shop-flash]').forEach(function (el) {
                    var ms = parseInt(el.getAttribute('data-auto-dismiss') || '5000', 10);
                    if (ms > 0) {
                        setTimeout(function () {
                            el.remove();
                        }, ms);
                    }
                });
            })();
        </script>
    @endpush

    {{-- Bagisto-like: desktop top-end, mobile bottom-center (single toast each) --}}
    <div
        class="fixed z-[1001] grid max-w-[calc(100vw-2rem)] gap-2.5 max-sm:bottom-10 max-sm:left-1/2 max-sm:w-full max-sm:max-w-[min(408px,calc(100vw-2rem))] max-sm:-translate-x-1/2 max-sm:translate-y-0 max-sm:justify-items-center max-sm:px-4 sm:top-5 sm:justify-items-end ltr:sm:right-5 rtl:sm:left-5"
        aria-live="polite"
    >
        @foreach ($shopFlashes as $flash)
            @include('shop::components.flash-group.item', ['flash' => $flash])
        @endforeach
    </div>
@endif
