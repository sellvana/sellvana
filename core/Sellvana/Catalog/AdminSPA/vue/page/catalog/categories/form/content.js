define(['sv-hlp', 'text!sv-page-catalog-categories-form-content-tpl'], function (SvHlp, tabTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: tabTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        },
        methods: {
            sortingUpdate: function () {

            }
        }
    }
});