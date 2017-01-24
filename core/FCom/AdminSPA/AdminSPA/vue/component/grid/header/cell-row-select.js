define(['sv-hlp', 'text!sv-comp-grid-header-cell-row-select-tpl'], function (SvHlp, gridHeaderCellRowSelectTpl) {
    return {
        mixins: [SvHlp.mixins.common],
        props: ['grid', 'col'],
        data: function () {
            return {
                showSelectedOnly: false
            }
        },
        template: gridHeaderCellRowSelectTpl,
        computed: {
            selectedCnt: function () {
                return this.grid && this.grid.rows_selected ? Object.keys(this.grid.rows_selected).length : 0;
            }
        },
        methods: {
            viewAll: function () {

            },
            viewSelected: function () {

            },
            selectVisible: function () {

            },
            unselectVisible: function () {

            },
            unselectAll: function () {

            }
        }
    }
});