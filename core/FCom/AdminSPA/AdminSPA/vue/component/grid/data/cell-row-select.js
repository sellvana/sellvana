define(['vue'], function (Vue) {
    return {
        props: ['grid', 'row', 'col'],
        template: '<th class="column-row-select">'
        + '<label @click.prevent="selectRow(col)"><input type="checkbox" :checked="isRowSelected(col)"/>'
        + '<span><i class="fa fa-check" aria-hidden="true"></i></span></label>'
        + '</th>',
        computed: {
            isRowSelected: function () {
                return function (col) {
                    return this.grid.rows_selected && this.grid.rows_selected[this.row[col.id_field]];
                }
            }
        },
        methods: {
            selectRow: function (col) {
                if (!this.grid.rows_selected) {
                    Vue.set(this.grid, 'rows_selected', {});
                }
                var rowId = this.row[col.id_field];
                Vue.set(this.grid.rows_selected, rowId, !this.grid.rows_selected[rowId]);
            }
        }
    };
});