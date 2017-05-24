define(['sv-hlp', 'text!sv-page-sales-orders-form-vendor-tpl'],
    function (SvHlp, tabVendorCommsTpl) {

    return {
        props: {
            form: {
                type: Object
            }
        },
        template: tabVendorCommsTpl
    };
});