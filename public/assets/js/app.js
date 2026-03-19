'use strict';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    if (! Alpine) {
        return;
    }

    Alpine.data('submitState', () => ({
        submitting: false,
        startSubmitting(event) {
            if (this.submitting) {
                event.preventDefault();

                return;
            }

            this.submitting = true;
        },
    }));
});
