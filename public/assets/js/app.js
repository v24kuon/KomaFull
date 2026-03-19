'use strict';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    if (! Alpine) {
        return;
    }

    Alpine.data('submitState', () => ({
        submitting: false,
        init() {
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    this.submitting = false;
                }
            });
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
