(function () {
    'use strict';

    if (window.ikrarusDeliveryZonesBound) {
        return;
    }

    window.ikrarusDeliveryZonesBound = true;

    var timer = null;

    function refreshOrder(attempt) {
        var component = window.BX
            && BX.Sale
            && BX.Sale.OrderAjaxComponent;

        if (!component || !document.getElementById('bx-soa-order-form')) {
            return;
        }

        // Дождаться завершения текущего запроса Битрикса.
        if (component.BXFormPosting === true) {
            if (attempt < 100) {
                timer = setTimeout(function () {
                    refreshOrder(attempt + 1);
                }, 200);
            }

            return;
        }

        component.sendRequest();
    }

    // Делегирование сохраняет работу после пересоздания полей.
    document.addEventListener('change', function (event) {
        var field = event.target;

if (
    !field
    || !field.closest('#bx-soa-order-form')
) {
    return;
}

var addressChanged =
    field.id === 'soa-property-7'
    || field.id === 'soa-property-19';

var profileChanged =
    field.name === 'PERSON_TYPE'
    || field.name === 'PROFILE_ID';

if (!addressChanged && !profileChanged) {
    return;
}

        clearTimeout(timer);

        timer = setTimeout(function () {
            refreshOrder(0);
        }, 300);
    });
})();