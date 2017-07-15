define(['sv-app-data', 'sv-mixin-form-tab', 'text!sv-page-catalog-categories-form-main-tpl'], function (SvAppData, SvMixinFormTab, tabMainTpl) {
    return {
        mixins: [SvMixinFormTab],
        template: tabMainTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        }
    }
});