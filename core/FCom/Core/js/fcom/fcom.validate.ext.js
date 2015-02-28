define(['jquery', 'jquery.validate'], function($) {
    function validateIP(value, elem, params) {
        var regex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/;
        return regex.test(value);
    }
    $.validator.addMethod('validateIP', validateIP, 'IP address is invalid.');
    function stockInputValidate(value, elem, params) {
        if (value < 0) {
            return false;
        }
        return true;
    }
    $.validator.addMethod('stockInputValidate',stockInputValidate , 'Stock item cannot have less than 0 quantity in stock.');
    $.validator.addClassRules("input-stock", {
        stockInputValidate: true
    });
    //todo: move validatePasswordSecurity to here
});