define(['sv-app-data', 'text!sv-page-catalog-products-form-custom_views-tpl'], function (SvAppData, tabMainTpl) {
    return {
        template: tabMainTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        }
    }
});