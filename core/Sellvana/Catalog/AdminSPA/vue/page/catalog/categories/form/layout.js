define(['sv-app-data', 'sv-mixin-form-tab', 'sv-comp-form-layout', 'text!sv-page-catalog-categories-form-layout-tpl'],
    function (SvAppData, SvMixinFormTab, SvCompFormLayout, tabTpl) {

    return {
        mixins: [SvMixinFormTab],
        template: tabTpl,
        props: ['form'],
        components: {
            'sv-comp-form-layout': SvCompFormLayout
        },
        data: function () {
            return {
                dict: SvAppData
            }
        }
    }
});