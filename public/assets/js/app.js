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
