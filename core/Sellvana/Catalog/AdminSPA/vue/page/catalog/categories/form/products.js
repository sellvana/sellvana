define(['text!sv-page-catalog-categories-form-products-tpl'], function (tabTpl) {
    return {
        template: tabTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        }
    }
});