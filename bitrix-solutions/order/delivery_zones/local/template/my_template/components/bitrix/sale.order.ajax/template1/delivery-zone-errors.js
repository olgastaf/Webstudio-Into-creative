(function () {
    'use strict';

    var component = window.BX && BX.Sale && BX.Sale.OrderAjaxComponent;
    if (!component || component.ikrarusZoneErrorsBound) {
        return;
    }
    component.ikrarusZoneErrorsBound = true;

    function outsideState(context) {
        var state = context.result && context.result.IKRARUS_DELIVERY_ADDRESS;
        var selected = context.getSelectedDelivery();
        return state && state.status === 'outside'
            && selected && Number(selected.ID) === Number(state.deliveryId)
            ? state : null;
    }

    // Replace only the location-required error; preserve unrelated errors.
    function translateErrors(value, state) {
        if (typeof value === 'string') {
            return /обязательн/i.test(value) && /местоположени/i.test(value)
                ? state.message : value;
        }
        if (!value || typeof value !== 'object') {
            return value;
        }
        var result = Array.isArray(value) ? [] : {};
        Object.keys(value).forEach(function (key) {
            result[key] = translateErrors(value[key], state);
        });
        return result;
    }

    var originalValidate = component.validateLocation;
    component.validateLocation = function (input, property, fieldName) {
        var state = outsideState(this);
        if (state && property && Number(property.ID) === Number(state.locationId)) {
            return [state.message];
        }
        return originalValidate.apply(this, arguments);
    };

    var originalShowErrors = component.showErrors;
    component.showErrors = function (errors, scroll, showAll) {
        var state = outsideState(this);
        if (state) {
            errors = translateErrors(errors || {}, state);
            var main = errors.MAIN;
            if (!Array.isArray(main)) {
                main = main ? [main] : [];
            }
            if (main.indexOf(state.message) < 0) {
                main.push(state.message);
            }
            errors.MAIN = main;
        }
        return originalShowErrors.call(this, errors, scroll, showAll);
    };

    var originalShowWarnings = component.showWarnings;
    component.showWarnings = function () {
        var state = outsideState(this);
        if (!state) {
            return originalShowWarnings.apply(this, arguments);
        }
        var title = this.params.MESS_DELIVERY_CALC_ERROR_TITLE;
        var text = this.params.MESS_DELIVERY_CALC_ERROR_TEXT;
        this.params.MESS_DELIVERY_CALC_ERROR_TITLE = state.message;
        this.params.MESS_DELIVERY_CALC_ERROR_TEXT = '';
        try {
            return originalShowWarnings.apply(this, arguments);
        } finally {
            this.params.MESS_DELIVERY_CALC_ERROR_TITLE = title;
            this.params.MESS_DELIVERY_CALC_ERROR_TEXT = text;
        }
    };
})();
