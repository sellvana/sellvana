define(['sv-hlp', 'text!sv-page-promotions-tpl'], function (SvHlp, formTpl) {
    var SvPagePromoForm = {
        mixins: [SvHlp.mixins.common],
        template: formTpl
    };

    return SvPagePromoForm;
});