define(['sv-hlp', 'text!sv-page-users-form-main-tpl'], function (SvHlp, tabMainTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        props: ['form'],
        template: tabMainTpl,
        data: function () {
            return {
                test: '',
                test1: ''
            }
        }
    }
});