define(['sv-app-data', 'sv-mixin-form-tab', 'text!sv-page-catalog-categories-form-content-tpl'], function (SvAppData, SvMixinFormTab, tabTpl) {
    return {
        mixins: [SvMixinFormTab],
        template: tabTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        },
        methods: {
            sortingUpdate: function () {

            }
        }
    }
});