define(['sv-hlp', 'text!sv-page-sales-orders-form-vendor-tpl'],
    function (SvHlp, tabVendorCommsTpl) {



    return {
        mixins: [SvHlp.mixins.common],
        props: ['form', 'entity'],
       /* props: {
            form: {
                type: Object
            }
        },*/
        template: tabVendorCommsTpl
    };
});