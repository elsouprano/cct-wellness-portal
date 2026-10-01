import './bootstrap';
import Alpine from 'alpinejs';
import SignaturePad from 'signature_pad';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.SignaturePad = SignaturePad;
window.Swal = Swal;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const submitBtn = document.getElementById('login-submit-btn');

    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Disable button and show loading state
            submitBtn.disabled = true;
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Logging in...</span>
            `;

            // Clear previous errors
            document.querySelectorAll('.text-sm.text-red-600.space-y-1, .text-sm.text-red-600').forEach(el => el.remove());

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
                    Swal.fire({
                        icon: 'success',
                        title: 'Logged in successfully',
                        text: 'Redirecting to your dashboard...',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        color: 'var(--color-foreground, #0f172a)',
                        confirmButtonColor: '#8b1014', // Maroon theme color
                        iconColor: '#8b1014'
                    }).then(() => {
                        window.location.href = data.redirect || '/portal/dashboard';
                    });
                } else {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;

                    // Handle validation errors
                    if (data.errors) {
                        for (const [field, messages] of Object.entries(data.errors)) {
                            const inputField = document.getElementById(field);
                            if (inputField) {
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'text-sm text-red-600 space-y-1 mt-1';
                                errorDiv.innerHTML = messages.map(msg => `<p>${msg}</p>`).join('');
                                inputField.parentNode.appendChild(errorDiv);
                            }
                        }
                    } else if (data.message) {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'text-sm text-red-600 mb-2 text-center';
                        errorDiv.textContent = data.message;
                        submitBtn.parentNode.insertBefore(errorDiv, submitBtn);
                    }
                }
            } catch (error) {
                console.error('Login error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'text-sm text-red-600 mb-2 text-center';
                errorDiv.textContent = 'Unable to connect to the server. Please try again.';
                submitBtn.parentNode.insertBefore(errorDiv, submitBtn);
            }
        });
    }

    // Delegated listener for logout forms
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Logged out successfully',
                        text: 'Redirecting to the login page...',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        color: 'var(--color-foreground, #0f172a)',
                        confirmButtonColor: '#8b1014', // Maroon theme color
                        iconColor: '#8b1014'
                    }).then(() => {
                        window.location.href = data.redirect || '/login';
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
