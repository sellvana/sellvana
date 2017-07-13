define(['lodash', 'sv-hlp', 'sv-comp-form-layout', 'text!sv-page-catalog-products-form-layout-tpl'], function (_, SvHlp, SvCompFormLayout, tabMainTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: tabMainTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        },
        computed: {
            layoutData: function () {
                return _.get(this.form, 'product.data_custom.layout', {});
            }
        }
    }
});