define(['sv-mixin-form-tab', 'text!sv-page-catalog-fields-form-info-tpl'], function (SvMixinFormTab, tabInfoTpl) {
        return {
            mixins: [SvMixinFormTab],
            template: tabInfoTpl,
            props: ['form'],
            data: function () {
                return {
                    tabName: 'info'
                }
            }
        }
    }
);