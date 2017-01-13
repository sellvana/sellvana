define(['text!sv-page-catalog-products-form-custom_views-tpl'], function (tabMainTpl) {
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