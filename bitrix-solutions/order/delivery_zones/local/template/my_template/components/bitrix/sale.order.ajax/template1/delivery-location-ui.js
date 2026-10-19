(function () {
    'use strict';

    var component = window.BX
        && BX.Sale
        && BX.Sale.OrderAjaxComponent;

    if (!component || component.ikrarusLocationUiBound) {
        return;
    }

    component.ikrarusLocationUiBound = true;

    var style = document.createElement('style');

    style.textContent =
        '#bx-soa-order.ikrarus-address-only [data-property-id-row="6"],'
        + '#bx-soa-order.ikrarus-address-only [data-property-id-row="18"]'
        + '{display:none!important;}';

    document.head.appendChild(style);

    function isZoneDelivery(context) {
        var delivery = null;

        if (context.deliveryBlockNode && context.deliveryHiddenBlockNode) {
            delivery = context.getSelectedDelivery();
        }

        if (delivery) {
            return [3, 6].indexOf(Number(delivery.ID)) >= 0;
        }

        var deliveries = context.result && context.result.DELIVERY;

        if (!deliveries) {
            return false;
        }

        return Object.keys(deliveries).some(function (key) {
            var item = deliveries[key];

            return item
                && [3, 6].indexOf(Number(item.ID)) >= 0
                && item.CHECKED === 'Y';
        });
    }

    function update(context) {
        var root = document.getElementById('bx-soa-order');

        if (root) {
            root.classList.toggle(
                'ikrarus-address-only',
                !!isZoneDelivery(context)
            );
        }
    }

    // Повторно применяем скрытие после обновления блоков.
    ['editOrder', 'editSection'].forEach(function (name) {
        var original = component[name];

        component[name] = function () {
            var result = original.apply(this, arguments);

            update(this);

            return result;
        };
    });

    var originalValidate = component.validateLocation;

    component.validateLocation = function (input, property, fieldName) {
        if (
            isZoneDelivery(this)
            && property
            && [6, 18].indexOf(Number(property.ID)) >= 0
        ) {
            var state = this.result
                && this.result.IKRARUS_DELIVERY_ADDRESS;

            if (state && state.status === 'outside') {
                // Сохраняем сообщение о недоступной доставке.
                return originalValidate.apply(this, arguments);
            }

            // LOCATION заполнится на сервере после ввода адреса.
            // Пустое скрытое поле не мешает перейти к вводу ADDRESS.
            // Серверные проверки остаются включенными.
            return [];
        }

        return originalValidate.apply(this, arguments);
    };

    if (component.result) {
        update(component);
    }
    BX.ready(function () {
        update(component);
    });
})();