define(['sv-hlp', 'text!sv-page-catalog-products-form-layout-tpl'], function (SvHlp, tabMainTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: tabMainTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        }
    }
});