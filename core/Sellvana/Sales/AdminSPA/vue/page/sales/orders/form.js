define(['sv-app', 'sv-comp-form', 'text!sv-page-sales-orders-form-nav-tpl', 'text!sv-page-sales-orders-form-info-tpl'],
    function (SvApp, SvCompForm, formNavTpl, formInfoTpl) {

    const FormNav = {
        template: formNavTpl
    };

    const FormInfo = {
        template: formInfoTpl
    };

    return {
        components: {
            'sv-comp-form': SvCompForm,
            'sv-page-sales-orders-form-nav': FormNav,
            'sv-page-sales-orders-form-info': FormInfo
        }
    };
});