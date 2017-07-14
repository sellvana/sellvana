define(['sv-hlp', 'text!sv-page-catalog-fields-form-info-tpl'], function (SvHlp, tabInfoTpl) {
        return {
            mixins: [SvHlp.mixins.formTab],
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