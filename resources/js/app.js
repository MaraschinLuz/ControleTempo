

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.data('appShell', () => ({
    menu: false,
    confirmationOpen: false,
    confirmationForm: null,
    confirmationTitle: 'Confirmar ação',
    confirmationMessage: '',
    confirmationButton: 'Confirmar',
    confirmationVariant: 'danger',
    confirmationPreviousFocus: null,

    init() {
        this.$el.addEventListener('submit', event => this.requestConfirmation(event), true);
    },

    requestConfirmation(event) {
        const form = event.target;

        if (! (form instanceof HTMLFormElement) || ! form.dataset.confirm) {
            return;
        }

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;

            return;
        }

        event.preventDefault();
        this.confirmationForm = form;
        this.confirmationTitle = form.dataset.confirmTitle || 'Confirmar ação';
        this.confirmationMessage = form.dataset.confirm;
        this.confirmationButton = form.dataset.confirmButton || 'Confirmar';
        this.confirmationVariant = form.dataset.confirmVariant || 'danger';
        this.confirmationPreviousFocus = document.activeElement;
        this.confirmationOpen = true;
        document.body.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.confirmationCancel?.focus());
    },

    confirmAction() {
        if (! this.confirmationForm) {
            return;
        }

        const form = this.confirmationForm;
        form.dataset.confirmed = 'true';
        this.closeConfirmation(false);
        form.requestSubmit();
    },

    cancelConfirmation() {
        this.closeConfirmation(true);
    },

    trapConfirmationFocus(event) {
        if (! this.confirmationOpen) {
            return;
        }

        const focusable = [
            this.$refs.confirmationCancel,
            this.$refs.confirmationConfirm,
        ].filter(Boolean);
        const currentIndex = focusable.indexOf(document.activeElement);
        const nextIndex = event.shiftKey
            ? (currentIndex <= 0 ? focusable.length - 1 : currentIndex - 1)
            : (currentIndex + 1) % focusable.length;

        event.preventDefault();
        focusable[nextIndex]?.focus();
    },

    closeConfirmation(restoreFocus) {
        this.confirmationOpen = false;
        this.confirmationForm = null;
        document.body.classList.remove('overflow-hidden');

        if (restoreFocus) {
            this.$nextTick(() => this.confirmationPreviousFocus?.focus());
        }
    },
}));

Alpine.start();
