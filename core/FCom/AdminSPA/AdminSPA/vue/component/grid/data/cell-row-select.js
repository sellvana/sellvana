define(['vue'], function (Vue) {
    return {
        props: ['grid', 'row', 'col'],
        template: '<th class="column-row-select">'
            + '<label @click.prevent="selectRow(col)"><input type="checkbox" :checked="isRowSelected(col)" class="f-input-checkbox f-input-checkbox-b"/>'
            + '<span><i class="fa fa-check" aria-hidden="true"></i></span></label>'
            + '</th>',

        methods: {
            isRowSelected: function n (col) {
                return this.grid.rows_selected && this.grid.rows_selected[this.row[col.id_field]];
            },
            selectRow: function (col) {
                if (!this.grid.rows_selected) {
                    this.$set(this.grid, 'rows_selected', {});
                }
                var rowId = this.row[col.id_field], rowsSel = this.grid.rows_selected;
                // Vue.set(this.grid.rows_selected, rowId, !rowsSel[rowId]);
                if (rowsSel[rowId]) {
                    Vue.delete(this.grid.rows_selected, rowId);
                } else {
                    this.$set(this.grid.rows_selected, rowId, this.row);
                }
            }
        }
    };
});