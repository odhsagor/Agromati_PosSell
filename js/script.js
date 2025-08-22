document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            this.classList.toggle('open');
        });
    }

    
    
    if (document.getElementById('loginForm')) {
        const form = document.getElementById('loginForm');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');
        const messageBox = document.getElementById('messageBox');
        const submitBtn = document.getElementById('submitBtn');
        const spinner = document.getElementById('spinner');
        const btnText = document.getElementById('btnText');
        const togglePwd = document.getElementById('togglePwd');
        const rememberEmail = document.getElementById('rememberEmail');

    
        const savedEmail = localStorage.getItem('agri_email');
        if (savedEmail) {
            email.value = savedEmail;
            rememberEmail.checked = true;
        }

        togglePwd.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            togglePwd.setAttribute('aria-pressed', String(show));
            togglePwd.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });

        function validateEmail() {
            const ok = email.validity.valid && email.value.trim().length > 3;
            emailError.classList.toggle('hidden', ok);
            return ok;
        }
        
        function validatePassword() {
            const ok = password.value.trim().length >= 8;
            passwordError.classList.toggle('hidden', ok);
            return ok;
        }

        function updateSubmitState() {
            submitBtn.disabled = !(validateEmail() && validatePassword());
        }

        email.addEventListener('input', updateSubmitState);
        password.addEventListener('input', updateSubmitState);

        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const emailOK = validateEmail();
            const passOK = validatePassword();
            if (!emailOK || !passOK) {
                messageBox.className = 'message-box error';
                messageBox.textContent = 'Please fix the errors above.';
                messageBox.classList.remove('hidden');
                return;
            }
            if (rememberEmail.checked) {
                localStorage.setItem('agri_email', email.value.trim());
            } else {
                localStorage.removeItem('agri_email');
            }
            submitBtn.disabled = true;
            spinner.classList.remove('hidden');
            btnText.textContent = 'Signing in…';

            setTimeout(() => {
                spinner.classList.add('hidden');
                btnText.textContent = 'Sign in';
                submitBtn.disabled = false;

                messageBox.className = 'message-box success';
                messageBox.textContent = 'Login successful! Redirecting… (demo)';
                messageBox.classList.remove('hidden');
            }, 900);
        });
    }
});