define(['text!sv-page-catalog-products-form-videos-tpl'], function (tabMainTpl) {
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