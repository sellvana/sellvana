define(['text!sv-page-users-form-main-tpl'], function (tabMainTpl) {
    return {
        props: ['form'],
        template: tabMainTpl,
        data: function () {
            return {
                test: false
            }
        }
    }
});