define(['sv-hlp', 'text!sv-page-modules-grid-datacell-run-level-tpl'], function (SvHlp, tpl) {
    var Component = {
        mixins: [SvHlp.mixins.formTab],
        props: ['grid', 'row', 'col'],
        template: tpl
    };

    return Component;
})