define(['sv-mixin-form-tab', 'text!sv-page-promotions-create-tpl'], function (SvMixinFormTab, CreateCouponTpl) {
    return {
        mixins: [SvMixinFormTab],
        template: CreateCouponTpl,
        props: ['form']
    }
});