define(['text!sv-page-customers-form-main-tpl'], function (tabMainTpl) {
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