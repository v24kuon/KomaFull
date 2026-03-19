'use strict';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    if (! Alpine) {
        return;
    }

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
