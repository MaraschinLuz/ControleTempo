

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

Alpine.data('demandBoard', config => ({
    demands: config.demands,
    draggingId: null,
    modalOpen: config.openOnError,
    statusMessage: '',
    form: config.oldForm || {
        id: null,
        title: '',
        description: '',
        project_id: '',
        status: 'pending',
        priority: 'medium',
        due_date: '',
    },

    column(status) {
        return this.demands.filter(demand => demand.status === status);
    },

    openCreate(status = 'pending') {
        this.form = {
            id: null,
            title: '',
            description: '',
            project_id: '',
            status,
            priority: 'medium',
            due_date: '',
        };
        this.modalOpen = true;
        this.$nextTick(() => this.$refs.demandTitle?.focus());
    },

    openEdit(demand) {
        this.form = {
            id: demand.id,
            title: demand.title,
            description: demand.description || '',
            project_id: String(demand.project_id),
            status: demand.status,
            priority: demand.priority,
            due_date: demand.due_date || '',
        };
        this.modalOpen = true;
        this.$nextTick(() => this.$refs.demandTitle?.focus());
    },

    closeModal() {
        this.modalOpen = false;
    },

    formAction() {
        return this.form.id
            ? config.updateUrl.replace('__ID__', this.form.id)
            : config.storeUrl;
    },

    deleteAction() {
        return config.deleteUrl.replace('__ID__', this.form.id);
    },

    startDragging(demand) {
        this.draggingId = demand.id;
    },

    async dropIn(status) {
        if (! this.draggingId) return;

        const id = this.draggingId;
        this.draggingId = null;
        await this.moveDemand(id, status);
    },

    async moveDemand(id, status) {
        const demand = this.demands.find(item => item.id === id);
        if (! demand || demand.status === status) return;

        const previousStatus = demand.status;
        demand.status = status;
        this.statusMessage = 'Salvando alteração...';

        try {
            const response = await fetch(config.statusUrl.replace('__ID__', id), {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                },
                body: JSON.stringify({ status }),
            });

            if (! response.ok) throw new Error('Não foi possível atualizar a demanda.');

            this.statusMessage = 'Status atualizado.';
        } catch (error) {
            demand.status = previousStatus;
            this.statusMessage = error.message;
        } finally {
            window.setTimeout(() => this.statusMessage = '', 2500);
        }
    },

    formatDate(date) {
        if (! date) return '';
        const [year, month, day] = date.split('-');
        return `${day}/${month}/${year}`;
    },

    isOverdue(demand) {
        const today = new Date();
        const localToday = [
            today.getFullYear(),
            String(today.getMonth() + 1).padStart(2, '0'),
            String(today.getDate()).padStart(2, '0'),
        ].join('-');

        return demand.due_date && demand.status !== 'completed' && demand.due_date < localToday;
    },
}));

Alpine.start();
