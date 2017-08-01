define(['sv-mixin-common', 'text!sv-page-catalog-import-products-status-tpl'], function (SvMixinCommon, tpl) {
    return {
        mixins: [SvMixinCommon],
        props: {
            c: Object
        },
        template: tpl
    }
});