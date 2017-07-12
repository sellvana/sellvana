define(['sv-hlp', 'text!sv-page-catalog-import-products-tpl'], function (SvHlp, tpl) {
    var Component = {
        mixins: [SvHlp.mixins.common],
        template: tpl
    };

    return Component;
});