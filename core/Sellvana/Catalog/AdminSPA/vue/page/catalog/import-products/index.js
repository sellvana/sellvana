define(['sv-mixin-common', 'text!sv-page-catalog-import-products-tpl'], function (SvMixinCommon, tpl) {
    var Component = {
        mixins: [SvMixinCommon],
        template: tpl
    };

    return Component;
});