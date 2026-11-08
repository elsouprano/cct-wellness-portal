<template x-teleport="body">
    <div
        x-data="confirmActionModal()"
        x-init="initModal()"
        @open-confirm-modal.window="open($event.detail)"
        @open-delete-modal.window="openDeleteUrl($event.detail)"
        @close-confirm-modal.window="close()"
        @close-delete-modal.window="close()"
        @keydown.escape.window="close()"
        x-show="isOpen"
        style="display: none;"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0"
    >
        <!-- Backdrop -->
        <div 
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
            @click="close()"
        ></div>

        <!-- Panel -->
        <div 
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white rounded-3xl overflow-hidden shadow-2xl transform transition-all w-full max-w-md border border-border"
            @click.stop
        >
            <div class="p-6 sm:p-8">
                <!-- Header with Icon and Title -->
                <div class="flex items-center gap-3.5 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         :class="type === 'delete' ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary'">
                        <template x-if="type === 'delete'">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </template>
                        <template x-if="type !== 'delete'">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </template>
                    </div>
                    <h3 class="text-xl font-heading font-bold text-foreground" x-text="title"></h3>
                </div>

                <!-- Message Body -->
                <p class="text-foreground/70 text-sm leading-relaxed mb-6" x-text="message"></p>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border/60">
                    <button type="button" @click="close()" :disabled="submitting" class="btn-secondary text-xs py-2 px-4 cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" @click="confirm()" :disabled="submitting"
                            class="text-xs py-2 px-4 rounded-xl font-semibold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="type === 'delete' ? 'bg-destructive text-white hover:bg-destructive/90' : 'btn-primary'">
                        <svg x-show="submitting" style="display: none;" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="confirmText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
function confirmActionModal() {
    return {
        isOpen: false,
        submitting: false,
        type: 'save',
        title: 'Save Changes',
        message: 'Are you sure you want to save these changes?',
        confirmText: 'Confirm',
        targetForm: null,
        standaloneUrl: null,

        initModal() {
            // Global initialization of form change tracking
            window.serializeFormState = function(form) {
                if (!form) return '';
                const fd = new FormData(form);
                fd.delete('_token');
                fd.delete('_method');
                const params = new URLSearchParams();
                for (const [k, v] of fd.entries()) {
                    if (v instanceof File) {
                        params.append(k, `${v.name}:${v.size}`);
                    } else {
                        params.append(k, v);
                    }
                }
                return params.toString();
            };

            const recordInitialState = (form) => {
                if (form && !form.dataset.initialState) {
                    form.dataset.initialState = window.serializeFormState(form);
                }
            };

            // Record initial state for all edit forms on page load
            document.querySelectorAll('form[data-confirm="edit"]').forEach(recordInitialState);

            // Dynamically record on focusin if not already recorded (e.g. Alpine modals)
            document.addEventListener('focusin', (e) => {
                const form = e.target.closest('form[data-confirm="edit"]');
                if (form && !form.dataset.initialState) {
                    recordInitialState(form);
                }
            });

            // Global submit interception
            if (!window.__confirmSubmitListenerAttached) {
                window.__confirmSubmitListenerAttached = true;
                document.addEventListener('submit', (e) => {
                    const form = e.target;
                    if (!form || !form.dataset || !form.dataset.confirm) return;

                    // If form has already been confirmed, allow standard browser submission
                    if (form.dataset.confirmed === 'true') {
                        return;
                    }

                    // 1. Client-side native HTML5 validation
                    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                        if (typeof form.reportValidity === 'function') {
                            form.reportValidity();
                        }
                        return;
                    }

                    // 2. Edit form check: If not modified, skip modal and proceed
                    if (form.dataset.confirm === 'edit') {
                        const initial = form.dataset.initialState;
                        if (initial !== undefined) {
                            const current = window.serializeFormState(form);
                            if (initial === current) {
                                // Nothing changed: skip confirmation modal
                                return;
                            }
                        }
                    }

                    // 3. Intercept submission and trigger confirmation modal
                    e.preventDefault();

                    const confirmMode = form.dataset.confirm;
                    const isDelete = confirmMode === 'delete';

                    window.dispatchEvent(new CustomEvent('open-confirm-modal', {
                        detail: {
                            form: form,
                            type: isDelete ? 'delete' : 'save',
                            title: form.dataset.confirmTitle || (isDelete ? 'Confirm Action' : 'Save Changes'),
                            message: form.dataset.confirmMessage || (isDelete ? 'Are you sure you want to delete this? This cannot be undone.' : 'Are you sure you want to save these changes?'),
                            confirmText: form.dataset.confirmButton || (isDelete ? 'Delete' : 'Confirm')
                        }
                    }));
                });
            }
        },

        open(detail) {
            this.targetForm = detail.form || null;
            this.standaloneUrl = detail.url || null;
            this.type = detail.type || 'save';
            this.title = detail.title || (this.type === 'delete' ? 'Confirm Action' : 'Save Changes');
            this.message = detail.message || (this.type === 'delete' ? 'Are you sure you want to delete this? This cannot be undone.' : 'Are you sure you want to save these changes?');
            this.confirmText = detail.confirmText || (this.type === 'delete' ? 'Delete' : 'Confirm');
            this.submitting = false;
            this.isOpen = true;
            document.body.classList.add('overflow-y-hidden');
        },

        openDeleteUrl(detail) {
            this.open({
                url: detail.url,
                type: 'delete',
                title: detail.title || 'Delete item?',
                message: detail.message || 'Are you sure you want to delete this? This cannot be undone.',
                confirmText: detail.confirmButton || 'Delete'
            });
        },

        close() {
            this.isOpen = false;
            this.submitting = false;
            this.targetForm = null;
            this.standaloneUrl = null;
            document.body.classList.remove('overflow-y-hidden');
        },

        confirm() {
            if (this.submitting) return;
            this.submitting = true;

            if (this.targetForm) {
                this.targetForm.dataset.confirmed = 'true';
                if (typeof this.targetForm.requestSubmit === 'function') {
                    this.targetForm.requestSubmit();
                } else {
                    this.targetForm.submit();
                }
            } else if (this.standaloneUrl) {
                // For direct action URLs (backward compatibility with open-delete-modal)
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.standaloneUrl;

                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrf) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrf;
                    form.appendChild(csrfInput);
                }

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    };
}
</script>

