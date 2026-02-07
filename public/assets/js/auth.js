document.addEventListener('DOMContentLoaded', () => {
    const authPage = document.querySelector('.auth-page');
    const toggleLinks = document.querySelectorAll('[data-auth-toggle]');
    const registerForm = document.getElementById('register-form');
    const registerErrors = document.getElementById('register-errors');
    const loginSuccess = document.getElementById('login-success');

    if (!authPage) {
        return;
    }

    const showPanel = (view) => {
        if (view === 'register') {
            authPage.classList.add('auth-page--register');
        } else {
            authPage.classList.remove('auth-page--register');
        }
    };

    toggleLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            showPanel(link.dataset.authToggle);
        });
    });

    if (registerForm) {
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (registerErrors) {
                registerErrors.hidden = true;
                const list = registerErrors.querySelector('ul');
                if (list) {
                    list.innerHTML = '';
                }
            }

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: new FormData(registerForm),
                });

                const data = await response.json();
                if (data.ok) {
                    showPanel('login');
                    if (loginSuccess) {
                        loginSuccess.textContent = data.message;
                        loginSuccess.hidden = false;
                    }
                    if (data.token) {
                        const loginToken = document.querySelector('form.auth-form input[name="csrf_token"]');
                        const registerToken = document.querySelector('#register-form input[name="csrf_token"]');
                        if (loginToken) {
                            loginToken.value = data.token;
                        }
                        if (registerToken) {
                            registerToken.value = data.token;
                        }
                    }
                    registerForm.reset();
                    return;
                }

                if (registerErrors) {
                    const list = registerErrors.querySelector('ul');
                    if (list) {
                        list.innerHTML = '';
                        (data.errors || []).forEach((error) => {
                            const item = document.createElement('li');
                            item.textContent = error;
                            list.appendChild(item);
                        });
                    }
                    registerErrors.hidden = false;
                }
            } catch (error) {
                if (registerErrors) {
                    const list = registerErrors.querySelector('ul');
                    if (list) {
                        list.innerHTML = '';
                        const item = document.createElement('li');
                        item.textContent = 'No se pudo completar el registro. Intenta de nuevo.';
                        list.appendChild(item);
                    }
                    registerErrors.hidden = false;
                }
            }
        });
    }
});
