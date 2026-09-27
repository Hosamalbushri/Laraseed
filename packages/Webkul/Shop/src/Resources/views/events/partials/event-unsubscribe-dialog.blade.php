<dialog
    id="shop-event-unsubscribe-dialog"
    class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl [&::backdrop]:bg-slate-900/50"
    aria-labelledby="shop-event-unsubscribe-dialog-title"
    aria-describedby="shop-event-unsubscribe-dialog-desc"
>
    <form
        method="dialog"
        class="flex flex-col gap-5 p-6 sm:p-8"
    >
        <div class="flex items-start justify-between gap-4">
            <h2
                id="shop-event-unsubscribe-dialog-title"
                class="text-lg font-bold tracking-tight text-slate-900"
            >
                {{ __('shop::app.student.events.unsubscribe-modal-title') }}
            </h2>

            <button
                type="submit"
                value="cancel"
                class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)]"
                aria-label="{{ __('shop::app.student.events.unsubscribe-modal-close') }}"
            >
                <span class="block text-xl leading-none" aria-hidden="true">&times;</span>
            </button>
        </div>

        <p
            id="shop-event-unsubscribe-dialog-desc"
            class="text-sm leading-relaxed text-slate-600"
        >
            <span id="shop-event-unsubscribe-dialog-body-prefix"></span>
            <strong
                id="shop-event-unsubscribe-dialog-event-name"
                class="font-semibold text-slate-900"
            ></strong>
            <span id="shop-event-unsubscribe-dialog-body-suffix"></span>
        </p>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button
                type="submit"
                value="cancel"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)]"
            >
                {{ __('shop::app.student.events.unsubscribe-modal-cancel') }}
            </button>

            <button
                type="button"
                id="shop-event-unsubscribe-dialog-confirm"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 active:bg-red-800"
            >
                {{ __('shop::app.student.events.unsubscribe-modal-confirm') }}
            </button>
        </div>
    </form>
</dialog>

@pushOnce('scripts', 'shop-event-unsubscribe-dialog')
    @php
        $shopEventUnsubscribeDialogI18n = [
            'bodyBefore' => __('shop::app.student.events.unsubscribe-modal-body-before'),
            'bodyAfter' => __('shop::app.student.events.unsubscribe-modal-body-after'),
            'genericError' => __('shop::app.events.subscribe.not-available'),
        ];
    @endphp
    <script>
        (function () {
            const dialog = document.getElementById('shop-event-unsubscribe-dialog');
            const nameEl = document.getElementById('shop-event-unsubscribe-dialog-event-name');
            const prefixEl = document.getElementById('shop-event-unsubscribe-dialog-body-prefix');
            const suffixEl = document.getElementById('shop-event-unsubscribe-dialog-body-suffix');
            const confirmBtn = document.getElementById('shop-event-unsubscribe-dialog-confirm');

            if (!dialog || !nameEl || !prefixEl || !suffixEl || !confirmBtn) {
                return;
            }

            const i18n = @json($shopEventUnsubscribeDialogI18n);

            let currentBtn = null;

            function csrfToken() {
                const m = document.querySelector('meta[name="csrf-token"]');

                return m ? m.getAttribute('content') || '' : '';
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-shop-event-unsubscribe]');

                if (!btn) {
                    return;
                }

                e.preventDefault();

                currentBtn = btn;

                const title = btn.getAttribute('data-event-title') || '';

                prefixEl.textContent = i18n.bodyBefore || '';
                nameEl.textContent = title;
                suffixEl.textContent = i18n.bodyAfter || '';

                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });

            confirmBtn.addEventListener('click', async function () {
                const btn = currentBtn;

                if (!btn) {
                    dialog.close();

                    return;
                }

                const unsubscribeUrl = btn.getAttribute('data-unsubscribe-url') || '';

                if (!unsubscribeUrl) {
                    dialog.close();

                    return;
                }

                confirmBtn.disabled = true;

                try {
                    const body = new FormData();

                    body.append('_token', csrfToken());

                    const res = await fetch(unsubscribeUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        credentials: 'same-origin',
                        body,
                    });

                    const data = await res.json().catch(function () {
                        return {};
                    });

                    if (!res.ok) {
                        window.alert(data.message || i18n.genericError || 'Error');
                        confirmBtn.disabled = false;
                        dialog.close();

                        return;
                    }

                    window.alert(data.message || '');
                    dialog.close();
                    window.location.reload();
                } catch (err) {
                    window.alert(i18n.genericError || '');
                    confirmBtn.disabled = false;
                    dialog.close();
                }
            });

            dialog.addEventListener('click', function (e) {
                if (e.target === dialog) {
                    dialog.close();
                }
            });

            dialog.addEventListener('close', function () {
                confirmBtn.disabled = false;
                currentBtn = null;
            });
        })();
    </script>
@endPushOnce
