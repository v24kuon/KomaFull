'use strict';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    if (! Alpine) {
        return;
    }

    Alpine.data('additionalItemForm', () => ({
        inputType: 'text',
        inputTypeEl: null,
        inputTypeChangeHandler: null,
        init() {
            const el = document.getElementById('input_type');
            if (!el) {
                return;
            }

            this.inputTypeEl = el;
            this.inputType = el.value;
            this.inputTypeChangeHandler = () => {
                this.inputType = el.value;
            };
            el.addEventListener('change', this.inputTypeChangeHandler);
        },
        destroy() {
            if (this.inputTypeEl === null || this.inputTypeChangeHandler === null) {
                return;
            }

            this.inputTypeEl.removeEventListener('change', this.inputTypeChangeHandler);
            this.inputTypeEl = null;
            this.inputTypeChangeHandler = null;
        },
    }));

    Alpine.data('bookingPaymentPending', (pollUrl) => ({
        statusLabel: '決済結果を確認しています…',
        terminalMessage: '',
        stopped: false,
        timer: null,
        pollUrl: typeof pollUrl === 'string' ? pollUrl : '',
        init() {
            if (this.pollUrl === '') {
                return;
            }

            this.timer = window.setInterval(() => {
                this.poll();
            }, 2000);

            this.poll();
        },
        destroy() {
            if (this.timer !== null) {
                window.clearInterval(this.timer);
                this.timer = null;
            }
        },
        async poll() {
            if (this.stopped || this.pollUrl === '') {
                return;
            }

            const pendingLabels = {
                pending_payment: '決済情報を確認しています…',
                processing: '枠の確定処理を実行しています…',
            };

            const terminalMessages = {
                refunded: '満席のため決済は返金済みです。別の枠をお選びください。',
                refund_failed: '返金処理に失敗しました。運営へお問い合わせください。',
                expired: 'お申し込みの有効期限が切れています。',
                canceled: 'お申し込みはキャンセルされました。',
            };

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                const response = await fetch(this.pollUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                });

                if (!response.ok) {
                    this.terminalMessage = '状態の取得に失敗しました。マイページでご確認ください。';
                    this.stopped = true;

                    return;
                }

                const data = await response.json();
                const status = data.status ?? '';

                if (status === 'reserved') {
                    this.stopped = true;
                    window.location.href = data.redirect_url ?? '/mypage';

                    return;
                }

                if (status === 'refund_pending') {
                    this.statusLabel = '満席のため返金手続き中です。完了までしばらくお待ちください…';

                    return;
                }

                if (Object.prototype.hasOwnProperty.call(terminalMessages, status)) {
                    this.stopped = true;
                    this.statusLabel = '';
                    this.terminalMessage = terminalMessages[status] ?? '';

                    return;
                }

                this.statusLabel = pendingLabels[status] ?? '処理中です…';
            } catch {
                this.terminalMessage = '通信に失敗しました。ページを再読み込みするか、マイページでご確認ください。';
                this.stopped = true;
            }
        },
    }));

    Alpine.data('submitState', () => ({
        submitting: false,
        pageShowHandler: null,
        init() {
            this.pageShowHandler = (event) => {
                if (event.persisted) {
                    this.submitting = false;
                }
            };

            window.addEventListener('pageshow', this.pageShowHandler);
        },
        destroy() {
            if (this.pageShowHandler === null) {
                return;
            }

            window.removeEventListener('pageshow', this.pageShowHandler);
            this.pageShowHandler = null;
        },
        startSubmitting(event) {
            if (this.submitting) {
                event.preventDefault();

                return;
            }

            this.submitting = true;
        },
    }));
});
