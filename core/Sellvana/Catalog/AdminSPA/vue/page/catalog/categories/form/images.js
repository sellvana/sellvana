define(['text!sv-page-catalog-categories-form-images-tpl'], function (tabTpl) {
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