define(['sv-hlp', 'text!sv-page-sales-orders-form-graph-tpl'],
    function (SvHlp, tabGraphTpl) {

        return {
            props: {
                form: {
                    type: Object
                }
            },
            template: tabGraphTpl

        };
    });