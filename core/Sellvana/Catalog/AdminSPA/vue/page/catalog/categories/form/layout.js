define(['sv-hlp', 'text!sv-page-catalog-categories-form-layout-tpl'], function (SvHlp, tabTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: tabTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        }
    }
});