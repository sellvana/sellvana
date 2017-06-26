define(['text!sv-page-modules-grid-datacell-run-level-tpl'], function (tpl) {
    var Component = {
        props: ['grid', 'row', 'col'],
        template: tpl
    };

    return Component;
})