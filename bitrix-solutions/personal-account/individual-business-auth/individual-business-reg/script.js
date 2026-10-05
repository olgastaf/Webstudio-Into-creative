(function () {
    'use strict';
    function initRegistration() {
        document.querySelectorAll('.registration-form').forEach(function (form) {
            if (form.dataset.initialized) return;
            form.dataset.initialized = 'Y';
            const radios = form.querySelectorAll('[data-client-type]');
            function updateBusinessFields() {
                const chosen = form.querySelector('[data-client-type]:checked');
                const business = Boolean(chosen && chosen.dataset.clientType === 'business');
                form.querySelectorAll('[data-business-field]').forEach(function (row) {
                    row.hidden = !business;
                    row.querySelectorAll('input, select, textarea').forEach(function (input) {
                        input.disabled = !business;
                        if (input.hasAttribute('data-business-required')) input.required = business;
                    });
                });
                const kpp = form.querySelector('[name="UF_KPP"]');
                const inn = form.querySelector('[name="UF_INN"]');
                // КПП обязательно только при 10-значном ИНН организации.
                if (kpp) kpp.required = business && inn && /^[0-9]{10}$/.test(inn.value.trim());
            }
            radios.forEach(function (radio) { radio.addEventListener('change', updateBusinessFields); });
            const inn = form.querySelector('[name="UF_INN"]');
            if (inn) inn.addEventListener('input', updateBusinessFields);
            if (radios.length) updateBusinessFields();
            form.addEventListener('submit', function () {
                const email = form.querySelector('[name="REGISTER[EMAIL]"]');
                const login = form.querySelector('[name="REGISTER[LOGIN]"]');
                const password = form.querySelector('[name="REGISTER[PASSWORD]"]');
                const confirmation = form.querySelector('[name="REGISTER[CONFIRM_PASSWORD]"]');
                const phone = form.querySelector('[name="REGISTER[PERSONAL_PHONE]"]');
                const authPhone = form.querySelector('[name="REGISTER[PHONE_NUMBER]"]');
                if (email && login) login.value = email.value.trim();
                if (password && confirmation) confirmation.value = password.value;
                if (phone && authPhone) authPhone.value = phone.value;
            });
        });

        const modal = document.getElementById('register-modal');
        if (!modal || modal.dataset.initialized) return;
        modal.dataset.initialized = 'Y';
        const box = modal.querySelector('.register-modal__box');
        const openers = document.querySelectorAll('[data-auth-view]');
        let previousFocus = null;
        let previousOverflow = '';
        function setView(view) {
            modal.querySelectorAll('[data-auth-panel]').forEach(function (panel) {
                panel.hidden = panel.dataset.authPanel !== view;
            });
            const titles = { registration: 'registration-title', login: 'authorization-title', forgot: 'recovery-title' };
            box.setAttribute('aria-labelledby', titles[view] || titles.login);
            box.scrollTop = 0;
        }
        function focusPanel() {
            const panel = modal.querySelector('[data-auth-panel]:not([hidden])');
            const message = panel && Array.from(panel.querySelectorAll('.registration-message')).find(function (node) { return !node.hidden; });
            (message || box).focus();
        }
        function openModal(view) {
            setView(view);
            if (modal.hidden) {
                previousFocus = document.activeElement;
                previousOverflow = document.body.style.overflow;
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            }
            focusPanel();
        }
        function closeModal() {
            if (modal.hidden) return;
            modal.hidden = true;
            document.body.style.overflow = previousOverflow;
            const target = previousFocus && previousFocus !== document.body ? previousFocus : document.getElementById('open-auth');
            if (target) target.focus();
        }
        openers.forEach(function (opener) {
            opener.addEventListener('click', function (event) {
                event.preventDefault();
                openModal(opener.dataset.authView);
            });
        });
        modal.querySelectorAll('[data-registration-close]').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });
        document.addEventListener('keydown', function (event) {
            if (modal.hidden) return;
            if (event.key === 'Escape') closeModal();
            if (event.key !== 'Tab') return;
            const nodes = Array.from(box.querySelectorAll('a[href], button, input, select, textarea, [tabindex="0"]'))
                .filter(function (node) { return !node.disabled && node.getClientRects().length; });
            if (!nodes.length) { event.preventDefault(); box.focus(); return; }
            const first = nodes[0], last = nodes[nodes.length - 1];
            if (event.shiftKey && (document.activeElement === first || !nodes.includes(document.activeElement))) {
                event.preventDefault(); last.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !nodes.includes(document.activeElement))) {
                event.preventDefault(); first.focus();
            }
        });
        if (modal.querySelector('[data-registration-open="Y"]')) openModal('registration');
        else if (modal.querySelector('[data-recovery-open="Y"]')) openModal('forgot');
        else if (modal.querySelector('[data-authentication-open="Y"]')) openModal('login');
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initRegistration);
    else initRegistration();
    if (window.BX && typeof window.BX.ready === 'function') window.BX.ready(initRegistration);
})();
