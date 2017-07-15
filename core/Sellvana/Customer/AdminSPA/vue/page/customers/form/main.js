define(['sv-app-data', 'text!sv-page-customers-form-main-tpl'], function (SvAppData, tabMainTpl) {
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