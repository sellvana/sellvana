define(['lodash', 'sv-app-data', 'sv-mixin-form-tab', 'sv-comp-form-layout', 'text!sv-page-catalog-products-form-layout-tpl'],
    function (_, SvAppData, SvMixinFormTab, SvCompFormLayout, tabMainTpl) {

    return {
        mixins: [SvMixinFormTab],
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