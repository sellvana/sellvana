define(['text!sv-page-catalog-products-form-main-tpl'], function (tabMainTpl) {
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