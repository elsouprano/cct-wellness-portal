import './bootstrap';
import Alpine from 'alpinejs';
import SignaturePad from 'signature_pad';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.SignaturePad = SignaturePad;
window.Swal = Swal;

function getCssToken(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

/**
 * Reusable client-side paginator for small server-rendered collections.
 * Syncs ?page= and ?limit= with URL query params and handles back/forward navigation.
 */
window.clientPaginator = function ({
    total = 0,
    defaultLimit = 10,
    pageParam = 'page',
    limitParam = 'limit',
    allowedLimits = [10, 20, 50],
} = {}) {
    return {
        total: Number(total) || 0,
        page: 1,
        limit: Number(defaultLimit) || 10,
        pageParam,
        limitParam,
        allowedLimits,
        loading: false,

        init() {
            this.readFromUrl();
            window.addEventListener('popstate', () => {
                this.readFromUrl();
            });
        },

        readFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const rawLimit = parseInt(params.get(this.limitParam), 10);
            if (this.allowedLimits.includes(rawLimit)) {
                this.limit = rawLimit;
            }
            const rawPage = parseInt(params.get(this.pageParam), 10);
            this.page = Number.isFinite(rawPage) ? this.clampPage(rawPage) : 1;
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.total / this.limit));
        },

        clampPage(target) {
            const n = parseInt(target, 10);
            if (!Number.isFinite(n) || n < 1) return 1;
            if (n > this.totalPages) return this.totalPages;
            return n;
        },

        get startIndex() {
            return (this.page - 1) * this.limit;
        },

        get endIndex() {
            return Math.min(this.total, this.startIndex + this.limit);
        },

        get fromItem() {
            return this.total === 0 ? 0 : this.startIndex + 1;
        },

        get toItem() {
            return this.endIndex;
        },

        isVisible(index) {
            return index >= this.startIndex && index < this.endIndex;
        },

        get visiblePages() {
            const total = this.totalPages;
            const current = this.page;
            const delta = 1;
            const pages = [];
            for (let i = 1; i <= total; i++) {
                if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
                    pages.push(i);
                } else if (pages[pages.length - 1] !== '...') {
                    pages.push('...');
                }
            }
            return pages;
        },

        goTo(target) {
            const next = this.clampPage(target);
            if (next === this.page) return;
            this.page = next;
            this.syncUrl();
        },

        setLimit(newLimit) {
            const parsed = parseInt(newLimit, 10);
            if (!this.allowedLimits.includes(parsed)) return;
            this.limit = parsed;
            this.page = 1;
            this.syncUrl();
        },

        syncUrl() {
            const url = new URL(window.location.href);
            url.searchParams.set(this.pageParam, String(this.page));
            url.searchParams.set(this.limitParam, String(this.limit));
            window.history.pushState({}, '', url.toString());
        },
    };
};

Alpine.start();

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        window.location.reload();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const submitBtn = document.getElementById('login-submit-btn');

    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            submitBtn.disabled = true;
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-on-primary inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Signing in...</span>
            `;

            document.querySelectorAll('.text-sm.text-destructive.space-y-1, .text-sm.text-destructive, .text-sm.text-red-600.space-y-1, .text-sm.text-red-600').forEach(el => el.remove());

            try {
                const formData = new FormData(loginForm);
                const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

                const response = await fetch(loginForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    const primaryColor = getCssToken('--color-primary', 'rgb(127 29 29)');
                    const foregroundColor = getCssToken('--color-foreground', 'rgb(28 25 23)');
                    Swal.fire({
                        icon: 'success',
                        title: 'Signed in',
                        text: 'Opening your dashboard...',
                        showConfirmButton: false,
                        timer: 1800,
                        timerProgressBar: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        color: foregroundColor,
                        confirmButtonColor: primaryColor,
                        iconColor: primaryColor
                    }).then(() => {
                        window.location.replace(data.redirect || '/dashboard');
                    });
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;

                    if (data.errors) {
                        for (const [field, messages] of Object.entries(data.errors)) {
                            const inputField = document.getElementById(field);
                            if (inputField) {
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'text-sm text-destructive space-y-1 mt-1';
                                messages.forEach(msg => {
                                    const p = document.createElement('p');
                                    p.textContent = msg;
                                    errorDiv.appendChild(p);
                                });
                                inputField.parentNode.appendChild(errorDiv);
                            }
                        }
                    } else if (data.message) {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'text-sm text-destructive mb-2 text-center';
                        errorDiv.textContent = data.message;
                        submitBtn.parentNode.insertBefore(errorDiv, submitBtn);
                    }
                }
            } catch (error) {
                console.error('Login error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;

                const errorDiv = document.createElement('div');
                errorDiv.className = 'text-sm text-destructive mb-2 text-center';
                errorDiv.textContent = 'Unable to connect to the server. Please try again.';
                submitBtn.parentNode.insertBefore(errorDiv, submitBtn);
            }
        });
    }

    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (form.tagName === 'FORM' && form.action.includes('/logout')) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]') || form.querySelector('input[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    const primaryColor = getCssToken('--color-primary', 'rgb(127 29 29)');
                    const foregroundColor = getCssToken('--color-foreground', 'rgb(28 25 23)');
                    Swal.fire({
                        icon: 'success',
                        title: 'Signed out',
                        text: 'Returning to sign-in page...',
                        showConfirmButton: false,
                        timer: 1800,
                        timerProgressBar: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        color: foregroundColor,
                        confirmButtonColor: primaryColor,
                        iconColor: primaryColor
                    }).then(() => {
                        window.location.replace(data.redirect || '/login');
                    });
                } else {
                    form.submit();
                }
            } catch (error) {
                console.error('Logout error:', error);
                form.submit();
            }
        }
    });
});
