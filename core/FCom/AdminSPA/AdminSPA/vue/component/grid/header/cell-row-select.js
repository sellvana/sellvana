define(['vue', 'text!sv-comp-grid-header-cell-row-select-tpl'], function (Vue, gridHeaderCellRowSelectTpl) {
    return {
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
            },
            hasSelectedOnPage: function () {
                if (!this.grid.rows_selected) {
                    Vue.set(this.grid, 'rows_selected', {});
                }
                var i, rowId;
                for (i in this.grid.rows) {
                    rowId = this.grid.rows[i][this.col.id_field];
                    if (this.grid.rows_selected[rowId]) {
                        return true;
                    }
                }
                return false;
            },
            hasUnselectedOnPage: function () {
                if (!this.grid.rows_selected) {
                    Vue.set(this.grid, 'rows_selected', {});
                }
                var i, rowId;
                for (i in this.grid.rows) {
                    rowId = this.grid.rows[i][this.col.id_field];
                    if (!this.grid.rows_selected[rowId]) {
                        return true;
                    }
                }
                return false;
            }
        },
        methods: {
            selectVisible: function () {
                if (!this.grid.rows_selected) {
                    Vue.set(this.grid, 'rows_selected', {});
                }
                var i, row, rowId;

                for (i in this.grid.rows) {
                    row = this.grid.rows[i];
                    rowId = row[this.col.id_field];
                    Vue.set(this.grid.rows_selected, rowId, row);
                }
            },
            unselectVisible: function () {
                if (!this.grid.rows_selected) {
                    Vue.set(this.grid, 'rows_selected', {});
                }
                var i, rowId;
                for (i in this.grid.rows) {
                    rowId = this.grid.rows[i][this.col.id_field];
                    Vue.delete(this.grid.rows_selected, rowId);
                }
            },
            unselectAll: function () {
                Vue.set(this.grid, 'rows_selected', {});
            },
            viewAll: function () {

            },
            viewSelected: function () {

            }
        }
    }
});