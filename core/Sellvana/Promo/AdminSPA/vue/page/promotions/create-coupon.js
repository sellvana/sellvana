define(['sv-hlp', 'text!sv-page-promotions-create-tpl'], function (SvHlp, CreateCouponTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: CreateCouponTpl,
        props: ['form']
    }
});