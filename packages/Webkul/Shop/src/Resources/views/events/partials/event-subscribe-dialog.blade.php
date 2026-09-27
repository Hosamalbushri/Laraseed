<dialog
    id="shop-event-subscribe-dialog"
    class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl [&::backdrop]:bg-slate-900/50"
    aria-labelledby="shop-event-subscribe-dialog-title"
    aria-describedby="shop-event-subscribe-dialog-desc"
>
    <form
        method="dialog"
        class="flex flex-col gap-5 p-6 sm:p-8"
    >
        <div class="flex items-start justify-between gap-4">
            <h2
                id="shop-event-subscribe-dialog-title"
                class="text-lg font-bold tracking-tight text-slate-900"
            >
                {{ __('shop::app.events.card.modal-title') }}
            </h2>

            <button
                type="submit"
                value="cancel"
                class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)]"
                aria-label="{{ __('shop::app.events.card.modal-close') }}"
            >
                <span class="block text-xl leading-none" aria-hidden="true">&times;</span>
            </button>
        </div>

        <p
            id="shop-event-subscribe-dialog-desc"
            class="text-sm leading-relaxed text-slate-600"
        >
            <span id="shop-event-subscribe-dialog-body-prefix"></span>
            <strong
                id="shop-event-subscribe-dialog-event-name"
                class="font-semibold text-slate-900"
            ></strong>
            <span id="shop-event-subscribe-dialog-body-suffix"></span>
        </p>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button
                type="submit"
                value="cancel"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)]"
            >
                {{ __('shop::app.events.card.modal-cancel') }}
            </button>

            <button
                type="button"
                id="shop-event-subscribe-dialog-confirm"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[color:var(--shop-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[color:var(--shop-primary-hover)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)] focus-visible:ring-offset-2"
            >
                {{ __('shop::app.events.card.modal-confirm') }}
            </button>
        </div>
    </form>
</dialog>

@pushOnce('scripts', 'shop-event-subscribe-dialog')
    @php
        $shopEventSubscribeDialogI18n = [
            'bodyBefore' => __('shop::app.events.card.modal-body-before'),
            'bodyAfter' => __('shop::app.events.card.modal-body-after'),
            'loginLead' => __('shop::app.events.subscribe.login-to-continue'),
            'confirmLogin' => __('shop::app.events.card.modal-confirm-login'),
            'confirmSubscribe' => __('shop::app.events.card.modal-confirm'),
            'genericError' => __('shop::app.events.subscribe.not-available'),
        ];
    @endphp
    <script>
        (function () {
            const dialog = document.getElementById('shop-event-subscribe-dialog');
            const nameEl = document.getElementById('shop-event-subscribe-dialog-event-name');
            const prefixEl = document.getElementById('shop-event-subscribe-dialog-body-prefix');
            const suffixEl = document.getElementById('shop-event-subscribe-dialog-body-suffix');
            const confirmBtn = document.getElementById('shop-event-subscribe-dialog-confirm');

            if (!dialog || !nameEl || !confirmBtn) {
                return;
            }

            const i18n = @json($shopEventSubscribeDialogI18n);

            let currentBtn = null;

            function csrfToken() {
                const m = document.querySelector('meta[name="csrf-token"]');

                return m ? m.getAttribute('content') || '' : '';
            }

            function setDialogCopyForGuest(title) {
                prefixEl.textContent = '';
                nameEl.textContent = title;
                suffixEl.textContent = ' — ' + (i18n.loginLead || '');
                confirmBtn.textContent = i18n.confirmLogin || '';
            }

            function setDialogCopyForStudent(title) {
                prefixEl.textContent = i18n.bodyBefore || '';
                nameEl.textContent = title;
                suffixEl.textContent = i18n.bodyAfter || '';
                confirmBtn.textContent = i18n.confirmSubscribe || '';
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-shop-event-subscribe]');

                if (!btn) {
                    return;
                }

                if (btn.disabled || btn.getAttribute('aria-disabled') === 'true') {
                    e.preventDefault();

                    return;
                }

                e.preventDefault();

                currentBtn = btn;

                const title = btn.getAttribute('data-event-title') || '';
                const auth = btn.getAttribute('data-student-auth') === '1';

                if (auth) {
                    setDialogCopyForStudent(title);
                } else {
                    setDialogCopyForGuest(title);
                }

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

                const auth = btn.getAttribute('data-student-auth') === '1';
                const loginUrl = btn.getAttribute('data-student-login-url') || '';
                const subscribeUrl = btn.getAttribute('data-subscribe-url') || '';

                if (!auth) {
                    if (loginUrl) {
                        window.location.href = loginUrl;
                    }

                    dialog.close();

                    return;
                }

                if (!subscribeUrl) {
                    dialog.close();

                    return;
                }

                confirmBtn.disabled = true;

                try {
                    const body = new FormData();

                    body.append('_token', csrfToken());

                    const res = await fetch(subscribeUrl, {
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
