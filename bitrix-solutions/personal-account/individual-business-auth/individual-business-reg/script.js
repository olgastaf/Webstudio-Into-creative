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
        const opener = document.getElementById('open-register');
        let previousFocus = null;
        let previousOverflow = '';
        function openModal() {
            if (!modal.hidden) return;
            previousFocus = document.activeElement;
            previousOverflow = document.body.style.overflow;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            const message = modal.querySelector('.registration-message');
            (message || box).focus();
        }
        function closeModal() {
            if (modal.hidden) return;
            modal.hidden = true;
            document.body.style.overflow = previousOverflow;
            const target = previousFocus && previousFocus !== document.body ? previousFocus : opener;
            if (target) target.focus();
        }
        if (opener) opener.addEventListener('click', openModal);
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
        if (modal.querySelector('[data-registration-open="Y"]')) openModal();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initRegistration);
    else initRegistration();
    // Если Битрикс выводит отложенный JS, повторная инициализация безопасна.
    if (window.BX && typeof window.BX.ready === 'function') window.BX.ready(initRegistration);
})();
