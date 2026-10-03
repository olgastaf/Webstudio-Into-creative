(function () {
    function initClientTypeSwitch() {
        document
            .querySelectorAll('.bx-auth-reg form[name="regform"]')
            .forEach(function (form) {
                const radios = form.querySelectorAll(
                    'input[name="UF_CLIENT_TYPE"][data-client-type]'
                );

                if (!radios.length) {
                    return;
                }

                const businessRows = form.querySelectorAll(
                    '[data-business-field]'
                );

                function updateFields() {
                    const selected = form.querySelector(
                        'input[name="UF_CLIENT_TYPE"]:checked'
                    );

                    const isBusiness = selected
                        && selected.dataset.clientType === 'business';

                    businessRows.forEach(function (row) {
                        row.hidden = !isBusiness;

                        row.querySelectorAll('input, select, textarea')
                            .forEach(function (input) {
                                input.disabled = !isBusiness;
                            });
                    });
                }

                radios.forEach(function (radio) {
                    radio.addEventListener('change', updateFields);
                });

                updateFields();
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initClientTypeSwitch
        );
    } else {
        initClientTypeSwitch();
    }
})();